$files = Get-ChildItem -Path . -Recurse -Filter *.html
foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw

    $logoPath = "assets/logo.png"
    if ($file.DirectoryName -match '(admin|student|partner)$') {
        $logoPath = "../assets/logo.png"
    }

    $modified = $false

    if ($content -match '<div class="sidebar-logo"><i class="fa-solid fa-utensils"></i></div>') {
        $content = $content -replace '<div class="sidebar-logo"><i class="fa-solid fa-utensils"></i></div>', ("<div class=""sidebar-logo""><img src=""" + $logoPath + """ alt=""Logo"" style=""max-width: 100%; max-height: 100%; object-fit: contain;""></div>")
        $modified = $true
    }

    if ($content -match '<div class="brand-icon"><i class="fa-solid fa-utensils"></i></div>') {
        $content = $content -replace '<div class="brand-icon"><i class="fa-solid fa-utensils"></i></div>', ("<div class=""brand-icon""><img src=""" + $logoPath + """ alt=""Logo"" style=""max-width: 100%; max-height: 100%; object-fit: contain;""></div>")
        $modified = $true
    }
    
    if ($content -match '<div class="landing-logo-icon"><i class="fa-solid fa-utensils"></i></div>') {
       $content = $content -replace '<div class="landing-logo-icon"><i class="fa-solid fa-utensils"></i></div>', ("<div class=""landing-logo-icon"" style=""background: none; box-shadow: none;""><img src=""" + $logoPath + """ alt=""Logo"" style=""max-width: 80px; height: auto;""></div>")
       $modified = $true
    }

    if ($content -match '<div class="logo"[^>]*><i class="fa-solid fa-utensils"></i></div>') {
       $content = $content -replace '<div class="logo"[^>]*><i class="fa-solid fa-utensils"></i></div>', ("<div class=""logo"" style=""width:120px;height:120px;display:flex;align-items:center;justify-content:center;margin:0 auto 32px;""><img src=""" + $logoPath + """ alt=""Logo"" style=""max-width: 100%; max-height: 100%; object-fit: contain;""></div>")
       $modified = $true
    }

    if ($modified) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        Write-Host "Updated $($file.Name)"
    }
}
