$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$loginPage = Invoke-WebRequest -Uri "http://127.0.0.1:8001/login" -WebSession $session -UseBasicParsing

$token = ""
if ($loginPage.Content -match 'name="_token"\s+value="([^"]+)"') {
    $token = $matches[1]
} elseif ($loginPage.Content -match '"csrfToken":"([^"]+)"') {
    $token = $matches[1]
}

Write-Host "CSRF Token: $token"

# In Laravel Breeze / Fortify with Inertia, login post is to /login
# Let's check headers or cookies
$cookies = $session.Cookies.GetCookies("http://127.0.0.1:8001")
$xsrfToken = ""
foreach ($cookie in $cookies) {
    if ($cookie.Name -eq "XSRF-TOKEN") {
        $xsrfToken = [System.Web.HttpUtility]::UrlDecode($cookie.Value)
    }
}
Write-Host "XSRF-TOKEN cookie: $xsrfToken"

$headers = @{
    "X-Requested-With" = "XMLHttpRequest"
    "X-XSRF-TOKEN" = $xsrfToken
    "Accept" = "application/json"
}

$body = @{
    username = "admin"
    password = "password"
} | ConvertTo-Json

$formFields = @{
    _token = $token
    username = "admin"
    password = "12345678"
}

try {
    $loginResponse = Invoke-WebRequest -Uri "http://127.0.0.1:8001/login" -Method Post -Body $formFields -WebSession $session -UseBasicParsing
    Write-Host "Login response: Status $($loginResponse.StatusCode)"
} catch {
    Write-Host "Login failed: $($_.Exception.Message)"
}

$testUrls = @(
    "http://127.0.0.1:8001/dashboard",
    "http://127.0.0.1:8001/indicator-selections?tipe=unit",
    "http://127.0.0.1:8001/indicators?scope=unit",
    "http://127.0.0.1:8001/data-entry",
    "http://127.0.0.1:8001/validation",
    "http://127.0.0.1:8001/data-analysis",
    "http://127.0.0.1:8001/unit/rtl",
    "http://127.0.0.1:8001/pdsa",
    "http://127.0.0.1:8001/indicator-selections?tipe=rs",
    "http://127.0.0.1:8001/hospital/data-capaian",
    "http://127.0.0.1:8001/hospital/data-capaian?kategori=IMN&rentang=triwulan",
    "http://127.0.0.1:8001/hospital/rtl",
    "http://127.0.0.1:8001/hospital/aggregation",
    "http://127.0.0.1:8001/hospital/analysis",
    "http://127.0.0.1:8001/hospital/pdsa",
    "http://127.0.0.1:8001/hospital/benchmark",
    "http://127.0.0.1:8001/supervisi",
    "http://127.0.0.1:8001/supervisi?sub=data_imut",
    "http://127.0.0.1:8001/supervisi?sub=rtl",
    "http://127.0.0.1:8001/reports"
)

Write-Host "`nTesting live URLs on running server:"
foreach ($url in $testUrls) {
    try {
        $res = Invoke-WebRequest -Uri $url -WebSession $session -UseBasicParsing
        Write-Host "[$($res.StatusCode)] $url"
    } catch {
        Write-Host "[ERR: $($_.Exception.Message)] $url"
    }
}
