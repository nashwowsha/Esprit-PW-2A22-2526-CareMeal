$dirs = @('admin', 'partner', 'student')

foreach ($dir in $dirs) {
    if (Test-Path $dir) {
        $files = Get-ChildItem -Path $dir -Filter "*.html"
        
        foreach ($file in $files) {
            $content = Get-Content $file.FullName -Raw

            $modified = $false
            if ($dir -eq 'admin' -and -not $content.Contains('"events.html"')) {
                $content = $content -replace '<a href="logs\.html" class="sidebar-link">', "            <a href=`"events.html`" class=`"sidebar-link`"><span class=`"link-icon`"><i class=`"fa-solid fa-calendar-day`"></i></span> Événements</a>`n            <a href=`"logs.html`" class=`"sidebar-link`">"
                $modified = $true
            }
            elseif ($dir -eq 'partner' -and -not $content.Contains('"events.html"')) {
                $content = $content -replace '<a href="stats\.html" class="sidebar-link">', "            <a href=`"events.html`" class=`"sidebar-link`"><span class=`"link-icon`"><i class=`"fa-solid fa-calendar-day`"></i></span> Événements</a>`n            <a href=`"stats.html`" class=`"sidebar-link`">"
                $modified = $true
            }
            elseif ($dir -eq 'student' -and -not $content.Contains('"events.html"')) {
                $content = $content -replace '<a href="orders\.html" class="sidebar-link">', "            <a href=`"events.html`" class=`"sidebar-link`">`n              <span class=`"link-icon`"><i class=`"fa-solid fa-calendar-day`"></i></span> Événements`n            </a>`n            <a href=`"orders.html`" class=`"sidebar-link`">"
                $modified = $true
            }

            if ($modified) {
                Set-Content -Path $file.FullName -Value $content -Encoding UTF8
                Write-Host "Updated $($file.Name) in $dir"
            }
        }
    }
}
