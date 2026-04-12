$dirs = @('admin', 'partner', 'student')

foreach ($dir in $dirs) {
    if (Test-Path $dir) {
        $files = Get-ChildItem -Path $dir -Filter "*.html"
        
        foreach ($file in $files) {
            $content = Get-Content $file.FullName -Raw

            $modified = $false
            if ($dir -eq 'admin' -and -not $content.Contains('"events.html"')) {
                $content = [System.Text.RegularExpressions.Regex]::Replace($content, '(<a href="logs\.html"[^>]*>)', "            <a href=`"events.html`" class=`"sidebar-link`"><span class=`"link-icon`"><i class=`"fa-solid fa-calendar-day`"></i></span> Événements</a>`n`$1")
                $modified = $true
            }
            elseif ($dir -eq 'partner' -and -not $content.Contains('"events.html"')) {
                $content = [System.Text.RegularExpressions.Regex]::Replace($content, '(<a href="stats\.html"[^>]*>)', "            <a href=`"events.html`" class=`"sidebar-link`"><span class=`"link-icon`"><i class=`"fa-solid fa-calendar-day`"></i></span> Événements</a>`n`$1")
                $modified = $true
            }
            elseif ($dir -eq 'student' -and -not $content.Contains('"events.html"')) {
                $content = [System.Text.RegularExpressions.Regex]::Replace($content, '(<a href="orders\.html"[^>]*>)', "            <a href=`"events.html`" class=`"sidebar-link`">`n              <span class=`"link-icon`"><i class=`"fa-solid fa-calendar-day`"></i></span> Événements`n            </a>`n`$1")
                $modified = $true
            }

            if ($modified) {
                Set-Content -Path $file.FullName -Value $content -Encoding UTF8
                Write-Host "Updated $($file.Name) in $dir"
            }
        }
    }
}
