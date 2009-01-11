Option Strict On

Imports System.Data.OleDb
Imports System.Collections.Specialized
Imports System.Net
Imports System.IO
Imports System.Text
Imports System.Text.RegularExpressions

Public Class Crawler

   ' Public
   Public Mode As String
   Public ProjectName As String

   ' Private
   Dim Log As Logger
   Public Conn As OleDbConnection
   Dim DB As SpiderDB
   Dim SystemConfig As MyDictionary
   Dim MaxDepth As Integer
   Dim MaxRetries As Integer
   Dim SpiderOnly As Boolean
   Dim ProjectEncoding As Encoding
   Dim ProjectConfig As MyDictionary
   Dim HTMLEntities As MyDictionary
   Dim DBContentTypes As MyDictionary
   Dim LinksProcessed As StringDictionary
   Dim SystemDateTags As String
   Dim ProjectUnwantedTags As String
   Dim SleepTime As Integer = 0
   Public Total As Integer = 0
   Dim CrawlMax As Integer = 0

   Public Sub New(ByVal Mode As String, ByVal Log As Logger, ByVal SystemConfig As MyDictionary, ByVal ProjectName As String, ByVal CrawlMax As Integer)
      Me.Mode = Mode
      Me.Log = Log
      Me.SystemConfig = SystemConfig
      Me.ProjectName = ProjectName
      Me.CrawlMax = CrawlMax
   End Sub

#Region "Init()"
   Sub Init()

      '''''''''''''''''''''''''''''''''''''''''

      ' Database Connection
      '''''''''''''''''''''''''''''''''''''''''

      Log.dpi("Connecting to DB...")
      Conn = New OleDbConnection(SystemConfig("connection"))
      Try
         Conn.Open()
         Log.dpi("Connected to DB...")
      Catch e As Exception
         Terminate("Unable to connect to database: " & e.Message)
      End Try

      '''''''''''''''''''''''''''''''''''''''''

      ' Project Configuration
      '''''''''''''''''''''''''''''''''''''''''

      ProjectConfig = New MyDictionary
      Log.dpi("Loading Project Configuration...")
      Try
         ProjectConfig.LoadColumns(Conn, "SELECT * FROM [project] WHERE [project]=" & foms(ProjectName))
         Log.dpd("Project Configuration:" & vbNewLine & ProjectConfig.ToString())
      Catch e As Exception
         Terminate("Unable to load configuration for project '" & ProjectName & "': " & e.Message)
      End Try

      '''''''''''''''''''''''''''''''''''''''''

      ' Validate Project Configuration
      '''''''''''''''''''''''''''''''''''''''''

      ' Validate start URL
      If LCase(URLFunctions.GetProtocol(ProjectConfig("start_url"))) <> "http" Or Not URLFunctions.IsValid(ProjectConfig("start_url")) Then
         Terminate("Invalid start_url protocol. Must be a valid http URL: " & ProjectConfig("start_url"))
      End If
      ' Validate domain settings
      If ProjectConfig("domains_allow") = "" And ProjectConfig("domains_reject") = "" Then
         ProjectConfig("domains_allow") = URLFunctions.GetDomain(ProjectConfig("start_url"))
         Log.dpi("Limiting crawl to domain: " & ProjectConfig("domains_allow"))
      End If
      SystemDateTags = SystemConfig("DateTags")
      ProjectUnwantedTags = SystemConfig("UnwantedTags")
      SleepTime = ParseInt(SystemConfig("SleepTime"), 0)

      ' Other settings
      MaxDepth = ParseInt(ProjectConfig("max_depth"), 0)
      MaxRetries = ParseInt(ProjectConfig("max_retries"), 0)
      SpiderOnly = (ParseInt(ProjectConfig("mode"), 0) = 1)

      ' Validate charset
      Try
         ProjectEncoding = Encoding.GetEncoding(ProjectConfig("charset"))
      Catch e As Exception
         If ProjectConfig("charset") <> "" Then
            Log.dpw("Invalid charset setting '" & ProjectConfig("charset") & "'")
         End If
         ProjectEncoding = Encoding.ASCII
         Log.dpw("Defaulting charset setting to '" & ProjectEncoding.BodyName & "'")
      End Try

      '''''''''''''''''''''''''''''''''''''''''

      ' Spider DB Functions
      '''''''''''''''''''''''''''''''''''''''''

      Log.dpi("Initialising DB...")
      DB = New SpiderDB(Conn, ProjectName, Mode, CSVInStr(SystemConfig("UnicodeCharsets"), ProjectEncoding.BodyName))
      DB.CommandTimeout = ParseInt(SystemConfig("CommandTimeout"), 30)
      DB.Log = Log

      '''''''''''''''''''''''''''''''''''''''''

      ' Load content_types table
      '''''''''''''''''''''''''''''''''''''''''

      Log.dpi("Loading Content Types...")
      DBContentTypes = New MyDictionary
      Try
         DBContentTypes.LoadRows(Conn, "SELECT [content_type], [extension] FROM [content_type]")
      Catch e As Exception
         Terminate("Unable to load content types: " & e.Message)
      End Try
      If DBContentTypes.Records.Count = 0 Then
         Terminate("Zero content types loaded. content_types table is empty.")
      End If

      '''''''''''''''''''''''''''''''''''''''''

      ' Load html_entity table
      '''''''''''''''''''''''''''''''''''''''''

      Log.dpi("Loading HTML Entities...")
      HTMLEntities = New MyDictionary
      Try
         HTMLEntities.LoadRows(Conn, "SELECT [entity], [character] FROM [html_entity]")
      Catch e As Exception
         Terminate("Unable to load html entities: " & e.Message)
      End Try
      If DBContentTypes.Records.Count = 0 Then
         Terminate("Zero html entities loaded. html_entity table is empty.")
      End If


   End Sub
#End Region

#Region "Crawl()"
   Sub Crawl()


      '''''''''''''''''''''''''''''''''''''''''

      ' Main Program
      '''''''''''''''''''''''''''''''''''''''''

      Log.dpi("Program Starting...")

      ' Crawl Mode
      If Mode = "resume" Then
         ' Resume previous crawl
         Log.dpa("Resume mode - Crawling incomplete pages in page store")
      ElseIf Mode = "refresh" Then
         ' Refresh pages
         Log.dpa("Refresh mode - Re-crawling existing pages and updating page store")
         Try
            DB.ResetPageStore(CrawlMax)
         Catch e As Exception
            Terminate("Unable to reset page store: " & e.Message)
         End Try
         Log.dpi("Page Store has been reset")
      Else
         ' Clear all pages
         Log.dpa("Recrawl mode - Clearing page store and recrawling all pages")
         Try
            DB.ClearPageStore()
         Catch e As Exception
            Terminate("Unable to clear page store: " & e.Message)
         End Try
         Log.dpi("Page Store has been cleared")
      End If

      ' Maintain Depth
      Dim SpiderDepth As Integer = 0

      ' Maintain list of links processed in memory
      LinksProcessed = New StringDictionary

      If Mode = "recrawl" Then
         ' Save starting page to DB for crawling
         Dim arrStartURLs As String()
         arrStartURLs = Split(ProjectConfig("start_url"), "|")
         Dim StartURL As String
         For Each StartURL In arrStartURLs
            If Trim(StartURL) <> "" Then
               DB.AddtoCrawl(StartURL, "", SpiderDepth, CrawlMax)
               LinksProcessed(StartURL) = "1"
            End If
         Next
      End If

      ' Fetch list of pages
      Dim SQLString As String
      SQLString = "SELECT"
      If CrawlMax > 0 Then SQLString = SQLString & " TOP " & CrawlMax
      SQLString = SQLString & " [id], [url], [referer], [depth], [state], [crawl_date], DATALENGTH([binary_content]) As datalen FROM [page]"
      ' Pages for this project
      SQLString = SQLString & " WHERE [project]=" & foms(ProjectName)
      ' Pages not yet retrieved
      SQLString = SQLString & " AND [state] > -3"
      ' Pages that have not been retried to often
      SQLString = SQLString & " AND [state] <= " & fomn(MaxRetries)
      ' Pages at maximum depth or shallower
      If MaxDepth >= 0 Then SQLString = SQLString & " AND [depth]<=" & fomn(MaxDepth)
      ' Fetch shallow pages first
      SQLString = SQLString & " ORDER BY [depth]"
      Log.dpd(SQLString)

      ' Fetch pages
      Dim DA As New OleDbDataAdapter
      DA.SelectCommand = New OleDbCommand(SQLString, Conn)
      Dim RS As New System.Data.DataSet
      DA.Fill(RS)

      If RS.Tables(0).Rows.Count > 0 Then
         ' Process table until no new pages
         Dim myRow1, myRow As System.Data.DataRow
         Do While RS.Tables(0).Rows.Count > 0

            ' Loop through all new pages
            For Each myRow In RS.Tables(0).Rows

               ' New URL
               Dim URL, Referer, Filename As String
               URL = myRow("url").ToString()
               SpiderDepth = CInt(myRow("depth").ToString())
               Referer = myRow("Referer").ToString()

               Log.dpi("Crawling URL: " & URL & " at depth " & SpiderDepth & " RS(state)=" & myRow("state").ToString())

               ' Update DB
               DB.MarkPageAsAttempted(URL)

               ' HTTP Headers
               Dim HTTPHeaders As String
               HTTPHeaders = ProjectConfig("http_headers")
               Dim CacheDate As DateTime = New Date(0)
               ' Don't GET page if we already
               If IsCachedResource(SystemConfig("CachedExtensions"), URL) And Not IsDBNull(myRow("crawl_date")) And Not IsDBNull(myRow("datalen")) Then
                  CacheDate = ParseDate(myRow("crawl_date").ToString(), New Date(0))
               End If

               ' Download page HTML
               Dim HTTPResponse As HttpWebResponse
               Dim HTTPStatus As Integer = 0
               Dim HTTPError As String = ""

               HTTPResponse = GetHTTPResponse(URL, HTTPHeaders, SystemConfig("UserAgent"), CacheDate, Referer, HTTPError, HTTPStatus)

               If IsReference(HTTPResponse) And HTTPError = "" Then

                  ' Check data size
                  If HTTPResponse.ContentLength = 0 And HTTPStatus = 200 Then
                     Log.dpw("Empty response body from URL: " & URL)
                  End If

                  ' Update DB
                  DB.MarkPageAsGot(URL, HTTPStatus)

                  If Not IsRedirect(HTTPStatus) Then

                     If IsBinaryResource(HTTPResponse) Then

                        ' Binary content
                        If Not SpiderOnly And HTTPStatus = 200 Then
                           Log.dpd("Updating binary resource " & URL)
                           ' Determine filename
                           Filename = DetermineFilenameBinary(URL, HTTPResponse)

                           ' Save binary content to database
                           DB.SaveBinaryResource(URL, Filename, HTTPResponse.GetResponseStream(), HTTPResponse.ContentType, HTTPResponse.ContentLength, HTTPResponse.LastModified)

                        ElseIf Not SpiderOnly Then
                           If HTTPStatus = 304 Then
                              Log.dpd("Keeping cached version of " & URL & " because of status " & HTTPStatus & " (unchanged).")
                           Else
                              Log.dpd("Not updating URL " & URL & " with status of " & HTTPStatus)
                           End If
                        End If

                     Else
                        ' Text content
                        Dim HTMLResponseText As String

                        ' Extract and decode response body
                        Dim ResponseEncoding As Encoding = DetermineEncoding(HTTPResponse, ProjectEncoding)
                        Dim SReader As New StreamReader(HTTPResponse.GetResponseStream, ResponseEncoding)
                        HTMLResponseText = SReader.ReadToEnd

                        If Not SpiderOnly Then

                           ' Determine filename
                           Filename = DetermineFilenameText(URL, HTTPResponse, ProjectConfig("default_document"))

                           ' Save text content to database
                           ExtractMetaDataToDB(URL, Filename, HTMLResponseText, HTTPResponse.GetResponseHeader("Content-Type"), HTTPResponse.ContentLength, HTTPResponse.Headers("Last-Modified"))
                        End If

                        ' Parse out links into DB
                        ExtractLinksToDB(URL, HTMLResponseText, SpiderDepth, SystemConfig("RegExLinks"))

                     End If

                  Else

                     ' Log redirect
                     Log.dpi("Redirected to URL: " & HTTPResponse.GetResponseHeader("Location"))

                     ' Add link to redirect
                     AddFoundLink(URL, HTTPResponse.GetResponseHeader("Location"), SpiderDepth)

                  End If

                  ' Finished with this page
                  DB.MarkPageAsParsed(URL)
                  Total = Total + 1

                  ' Sleep
                  If SleepTime > 0 Then System.Threading.Thread.Sleep(SleepTime)

               Else

                  ' HTTP Errors
                  If HTTPStatus = 0 Then
                     Log.dpe(HTTPError)
                  Else
                     Log.dpw(HTTPError)
                  End If
                  DB.MarkPageStatus(URL, HTTPStatus)

               End If

               ' Clean up
               If Not HTTPResponse Is Nothing Then
                  HTTPResponse.Close()
                  HTTPResponse = Nothing
               End If

               ' Maintain Max
               If CrawlMax <> 0 And Total >= CrawlMax Then Exit For

            Next

            ' Close data set
            RS.Reset()

            ' Get new urls
            If CrawlMax <> 0 And Total >= CrawlMax Then
               Exit Do
            Else
               Log.dpd(SQLString)
               DA.Fill(RS)
            End If
         Loop

      Else

         ' No Start URL found!
         Log.dpe("No URLs found to crawl. Use the recrawl option or check configuration of project (start_url, max_depth, max_retries).")

      End If

      ' Delete pages which were not found
      'If (Mode = "refresh" And CrawlMax = 0) Or Mode = "recrawl" Then
      '   Log.dpi("Deleting unrefreshed pages")
      '   DB.DeleteUnrefreshedPages()
      'End If

      '''''''''''''''''''''''''''''''''''''''''

      ' Shutdown
      '''''''''''''''''''''''''''''''''''''''''

      Log.dpi("Cleaning up...")
      LinksProcessed.Clear()
      RS.Reset()
      Log.dpi("Closing DB Connection...")
      Conn.Close()
      Log.dpi("Spider Completed...")

   End Sub
#End Region

#Region "Private Functions"
   Private Sub ExtractLinksToDB(ByVal URL As String, ByRef HTML As String, ByVal Depth As Integer, ByVal RegExLinks As String)
      ' Extract all links from the HTML of a URL

      ' Meta Robots Tag
      Dim RobotTag As String
      RobotTag = HTMLParser.ExtractMetaContents(HTML, "robots")
      If InStr(LCase(RobotTag), "nofollow") > 0 Then
         Log.dpi("Robots 'nofollow' tag in URL: " & URL)
         Exit Sub
      End If

      ' Remove comments & unwanted tags
      HTML = HTMLParser.RemoveComments(HTML)
      HTML = HTMLParser.RemoveTags(HTML, ProjectUnwantedTags)

      Dim LinkTags As MatchCollection
      LinkTags = RegExFunctions.GetMatches(HTML, RegExLinks)
      Log.dpi("Found " & LinkTags.Count & " links in URL: " & URL)

      ' Do we have at least 1 link
      If LinkTags.Count > 0 Then
         Dim LinkTag As Match
         For Each LinkTag In LinkTags
            If LinkTag.Groups.Count >= 1 Then
               AddFoundLink(URL, LinkTag.Groups(1).ToString(), Depth)
            End If
         Next
      End If

   End Sub

   Private Sub ExtractMetaDataToDB(ByVal URL As String, ByVal Filename As String, ByRef HTML As String, ByVal ContentType As String, ByVal ContentLength As Long, ByVal LastModified As String)
      ' Extract all links from the HTML of a URL

      ' Sometimes Content-Length is not defined
      If ContentLength = -1 Then
         ContentLength = Len(HTML)
      End If

      ' Meta Robots Tag
      Dim RobotTag As String
      RobotTag = HTMLParser.ExtractMetaContents(HTML, "robots")
      If InStr(LCase(RobotTag), "noindex") > 0 Then
         Log.dpi("Robots 'noindex' tag in URL: " & URL)
         DB.MarkPageAsIndexed(URL)
         Exit Sub
      End If

      ' Determine Modified date
      ' 1. First try meta tags
      Dim DateTag, DateString, DateTags() As String
      DateTags = Split(SystemDateTags, ",")
      For Each DateTag In DateTags
         DateString = HTMLParser.ExtractMetaContents(HTML, DateTag)
         If IsDate(DateString) Then Exit For
      Next
      Dim ModifiedDate As Date
      ModifiedDate = ParseDate(DateString, New Date(0))
      ' 2. Fall back on the HTTP Header
      If ModifiedDate.Ticks = 0 Then ModifiedDate = ParseDate(LastModified, New Date(0))
      ' 3. Fall back on todays date
      If ModifiedDate.Ticks = 0 Then ModifiedDate = Now()

      ' Extract the contents of the <title> tag
      Dim HTMLTitle As String
      HTMLTitle = HTMLParser.ExtractTagContents(HTML, "title")
      HTMLTitle = HTMLParser.Decode(HTMLTitle, HTMLEntities.Records)

      ' Extract the raw text
      Dim HTMLText As String = HTML
      HTMLParser.ExtractText(HTMLText, ProjectUnwantedTags)
      HTMLText = HTMLParser.Decode(HTMLText, HTMLEntities.Records)

      ' Compact text by removing unnecessary whitespace
      HTMLText = RegExFunctions.Replace(HTMLText, "\s+", " ")
      HTMLText = Trim(HTMLText)

      ' Save to DB
      Try
         DB.SaveMetaDataToDB(URL, Filename, HTMLTitle, HTMLText, ContentType, ContentLength, ModifiedDate)
      Catch e As Exception
         Log.dpe("Error saving meta data. Possible corrupt file at URL: " & URL)
      End Try

      ' Mark URL as indexed
      DB.MarkPageAsIndexed(URL)

   End Sub

   Private Sub AddFoundLink(ByVal URL As String, ByVal LinkURL As String, ByVal Depth As Integer)
      ' Validate and add link for crawling

      ' Check Depth
      If Depth >= MaxDepth And MaxDepth <> -1 Then
         Log.dpi("Maximum depth exceeded, ignoring link to : " & LinkURL & " from " & URL)
         Exit Sub
      End If

      ' Get absolute link
      LinkURL = URLFunctions.GetAbsoluteLink(URL, LinkURL)

      ' Decode from HTML
      LinkURL = URLFunctions.DecodeHTMLURL(LinkURL)

      ' Remove parameters if required
      If ProjectConfig("params_remove") <> "" And InStr(LinkURL, "?") > 0 Then
         Try
            LinkURL = RegExFunctions.Replace(LinkURL, "&(" & MakePattern(ProjectConfig("params_remove")) & ")=[^&]+", "")
            LinkURL = RegExFunctions.Replace(LinkURL, "\?(" & MakePattern(ProjectConfig("params_remove")) & ")=[^&]+", "?")
         Catch e As Exception
            Log.dpe("Error in Crawler.AddFoundLink() with RegEx Pattern params_remove='" & ProjectConfig("params_remove") & "': " & e.Message)
         End Try
         LinkURL = URLFunctions.Normalise(LinkURL)
      End If

      ' Check already fetched
      If LinksProcessed(LinkURL) = "1" Then
         Log.dpd("Already analysed, ignoring link to : " & LinkURL & " from " & URL)
         Exit Sub
      End If
      ' Remember that we've seen this link
      LinksProcessed(LinkURL) = "1"

      ' Validate
      If IsValidLink(URL, LinkURL) Then
         ' Save link to database
         DB.SaveLinkToDB(URL, LinkURL)

         ' Save page to database for fetching
         DB.AddtoCrawl(LinkURL, URL, Depth + 1, CrawlMax)
      End If

   End Sub

   Private Function IsValidLink(ByVal URL As String, ByVal LinkURL As String) As Boolean
      ' Returns true if the link should be crawled

      ' Check domain
      If Not IsLinkMatchPos(URLFunctions.GetDomain(LinkURL), ProjectConfig("domains_allow")) Then
         Log.dpi("No match of domains_allow, ignoring link to : " & LinkURL & " from " & URL)
         Return False
      End If
      If IsLinkMatchNeg(URLFunctions.GetDomain(LinkURL), ProjectConfig("domains_reject")) Then
         Log.dpi("Match of domains_reject, ignoring link to : " & LinkURL & " from " & URL)
         Return False
      End If

      ' Check path
      If Not IsLinkMatchPos(LinkURL, ProjectConfig("urls_allow")) Then
         Log.dpi("No match of urls_allow, ignoring link to : " & LinkURL & " from " & URL)
         Return False
      End If
      If IsLinkMatchNeg(URLFunctions.GetRelative(LinkURL), ProjectConfig("urls_reject")) Then
         Log.dpi("Match of urls_reject, ignoring link to : " & LinkURL & " from " & URL)
         Return False
      End If

      ' Check extension
      Dim Ext As String
      Ext = URLFunctions.GetExtension(LinkURL)
      If Ext = "" Then Ext = URLFunctions.GetExtension(URLFunctions.GetRelativeStem(LinkURL) & ProjectConfig("default_document"))
      If Not IsLinkMatchPos(Ext, ProjectConfig("extensions_allow")) Then
         Log.dpi("No match of extensions_allow, ignoring link to : " & LinkURL & " from " & URL)
         Return False
      End If
      If IsLinkMatchNeg(Ext, ProjectConfig("extensions_reject")) Then
         Log.dpi("Match of extensions_reject, ignoring link to : " & LinkURL & " from " & URL)
         Return False
      End If

      ' Stay in http protocol
      If Left(LCase(LinkURL), 7) <> "http://" Then
         Log.dpi("Ignoring non http:// link URL: " & LinkURL)
         Return False
      End If

      ' Passed all tests
      Return True

   End Function

   Private Function IsLinkMatchPos(ByVal URL As String, ByVal Pattern As String) As Boolean
      ' Returns true if pattern is empty, else does regex

      If Pattern = "" Then
         Return True
      Else
         Try
            Pattern = MakePattern(Pattern)
            Return RegExFunctions.IsMatch(URL, Pattern)
         Catch e As Exception
            Throw New Exception("Error in IsLinkMatchPos(" & URL & ", " & Pattern & ") : " & e.Message)
         End Try
      End If

   End Function

   Private Function IsLinkMatchNeg(ByVal URL As String, ByVal Pattern As String) As Boolean
      ' Returns false if pattern is empty, else does regex

      If Pattern = "" Then
         Return False
      Else
         Try
            Pattern = MakePattern(Pattern)
            Return RegExFunctions.IsMatch(URL, Pattern)
         Catch e As Exception
            Throw New Exception("Error in IsLinkMatchNeg(" & URL & ", " & Pattern & ") : " & e.Message)
         End Try
      End If

   End Function

   Private Function MakePattern(ByVal Pattern As String) As String
      ' Ensure this is a RegEx pattern

      ' This is a CSV List not a RegEx
      Pattern = Replace(Pattern, ", ", ",")
      Pattern = Replace(Pattern, ",", "|")
      Return Pattern

   End Function

   Private Function IsBinaryResource(ByVal HTTPResponse As HttpWebResponse) As Boolean
      ' Determines if the resource is binary based on Mime type

      ' Must catch errors in case header is missing
      Dim ContentType As String
      Try
         ContentType = HTTPResponse.GetResponseHeader("Content-Type")
      Catch e As Exception
         ' Who cares if it's missing?
      End Try

      If Left(LCase(ContentType), 4) = "text" Then
         Return False
      Else
         Return True
      End If

   End Function

   Private Function IsCachedResource(ByVal CachedExtensions As String, ByVal URL As String) As Boolean
      ' Determines if the URL could be cached (binaries)

      Dim Ext As String = LCase(URLFunctions.GetExtension(URL))
      If CSVInStr(CachedExtensions, Ext) Then
         Return True
      Else
         Return False
      End If

   End Function

   Private Function IsRedirect(ByVal Status As Integer) As Boolean
      ' Determine if a redirect occured

      If Status = 301 Or Status = 302 Then
         Return True
      Else
         Return False
      End If

   End Function

   Private Function CheckPageStatus(ByVal Status As Integer, ByVal URL As String) As Boolean
      ' Determine if status of a HTTP Request is OK

      CheckPageStatus = True

      ' Check HTTP status code
      Log.dpd("HTTP status code (" & Status & ") accessing URL: " & URL)
      ' Catch 40x and 50x errors
      If Status >= 400 And Status < 600 Then
         Log.dpw("HTTP Error code (" & Status & ") accessing URL: " & URL)
         CheckPageStatus = False
      End If

   End Function

   Private Function DetermineFilenameText(ByVal URL As String, ByVal HTTPResponse As HttpWebResponse, ByVal DefaultDoc As String) As String
      ' Determine the filename of a text resource

      ' Try get filename from URL
      Dim Filename As String
      Filename = URLFunctions.GetFilename(URL)

      ' If we got no filename check default document
      If Filename = "" Then Filename = DefaultDoc

      ' Add standard extension based on content type header
      Filename = AddStandardExtension(Filename, HTTPResponse.GetResponseHeader("Content-Type"))

      ' If we got no filename, assume index.htm
      If Filename = "" Then Filename = "index.htm"

      Return Filename

   End Function

   Private Function DetermineFilenameBinary(ByVal URL As String, ByVal HTTPResponse As HttpWebResponse) As String
      ' Determine the filename of a binary resource

      ' Try the content disposition header
      Dim Filename As String
      ' Header may not exist
      Try
         Filename = HTTPResponse.GetResponseHeader("Content-Disposition")
         Filename = After(Filename, "filename=")
      Catch e As Exception
      End Try

      ' Try URL
      If Filename = "" Then Filename = URLFunctions.Decode(URLFunctions.GetFilename(URL))

      ' Add standard extension based on content type header
      If Filename = "" Then Filename = AddStandardExtension(Filename, HTTPResponse.GetResponseHeader("Content-Type"))

      Return Filename

   End Function

   Private Function AddStandardExtension(ByVal Filename As String, ByVal ContentType As String) As String

      If InStr(ContentType, ";") > 0 Then ContentType = Before(ContentType, ";")

      Dim StandardExtension As String
      StandardExtension = DBContentTypes(ContentType)

      If LCase(URLFunctions.GetExtension(Filename)) <> StandardExtension And StandardExtension <> "" Then
         Filename = Filename & "." & StandardExtension
      ElseIf StandardExtension = "" Then
         Log.dpw("Unknown content type " & ContentType)
      End If

      Return Filename

   End Function

   Private Function DetermineEncoding(ByVal HTTPResponse As HttpWebResponse, ByVal ProjectEncoding As Encoding) As Encoding
      Try
         Return Encoding.GetEncoding(HTTPResponse.CharacterSet)
      Catch e As Exception
      End Try

      Try
         Return Encoding.GetEncoding(After(HTTPResponse.ContentType, "charset="))
      Catch e As Exception
      End Try

      Return ProjectEncoding
   End Function

   Private Function GetHTTPResponse(ByVal URL As String, ByVal CustomHeaders As String, ByVal UserAgent As String, ByVal IfModifiedSince As DateTime, ByVal Referer As String, ByRef ErrorMsg As String, ByRef HTTPStatus As Integer) As HttpWebResponse

      Dim HTTPClient As HttpWebRequest = CType(WebRequest.Create(URL), HttpWebRequest)

      HTTPClient.Timeout = 10000

      ' Standard headers
      If UserAgent <> "" Then HTTPClient.UserAgent = UserAgent
      If Referer <> "" Then HTTPClient.Referer = Referer
      If IfModifiedSince.Ticks > 0 Then HTTPClient.IfModifiedSince = IfModifiedSince

      ' Don't allow automatic redirections
      HTTPClient.AllowAutoRedirect = False

      ' Custom headers
      ' Set request headers
      Dim arrHeaders() As String
      Dim header As String
      arrHeaders = Split(CustomHeaders, vbNewLine)
      For Each header In arrHeaders
         If Trim(header) <> "" And InStr(header, ":") > 1 Then
            Try
               HTTPClient.Headers.Add(Trim(Before(header, ":")), Trim(After(header, ":")))
            Catch e As ArgumentException
               Log.dpw("Unable to add header in GetHTTPResponse() : " & header & vbNewLine & e.Message)
            End Try
         End If
      Next

      Dim HTTPResponse As HttpWebResponse
      Try
         HTTPResponse = CType(HTTPClient.GetResponse(), HttpWebResponse)
         HTTPStatus = CInt(HTTPResponse.StatusCode)
      Catch e As Exception
         HTTPStatus = ParseInt(Between(e.Message, "(", ")"))
         ErrorMsg = "Error in GetHTTPResponse(" & URL & ") accessing GetResponse(): " & e.Message
      End Try

      Return HTTPResponse

   End Function

   Private Sub Terminate(ByVal Msg As String)
      ' Terminate in error

      Me.Dispose()
      Throw New Exception(Msg)

   End Sub

   Sub Dispose()
      If Conn.State = ConnectionState.Open Then Conn.Close()
   End Sub

#End Region

End Class
