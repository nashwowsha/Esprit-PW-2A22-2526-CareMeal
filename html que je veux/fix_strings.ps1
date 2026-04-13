
$files = Get-ChildItem -Path "partner", "admin", "student" -Filter "*.html"
foreach ($f in $files) {
    $c = Get-Content $f.FullName -Raw -Encoding UTF8
    $c = $c -replace "ï¿½Ã‰tablissement", "Établissement"
    $c = $c -replace "ï¿½Ã©tablissement", "Établissement"
    $c = $c -replace "Mon ï¿½Ã‰", "Mon É"
    $c = $c -replace "ï¿½ CareMeal", "— CareMeal"
    $c = $c -replace "ï¿½", "—"
    $c = $c -replace "Ã©", "é"
    $c = $c -replace "Ã‰", "É"
    $c = $c -replace "Ã¨", "è"
    $c = $c -replace "Ã§", "ç"
    $c = $c -replace "Ãª", "ê"
    $c = $c -replace "Ã", "à"
    Set-Content $f.FullName -Value $c -Encoding UTF8
}
