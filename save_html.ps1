$file = 'c:\xampp\htdocs\Kaizen-Final-Back\frontend\rh\dashboard.php'
$lines = Get-Content $file -Encoding UTF8

# Guardar solo el HTML (líneas 0 a 928 inclusive)
$htmlPart = $lines[0..928]
[System.IO.File]::WriteAllLines($file, $htmlPart, [System.Text.UTF8Encoding]::new($false))
Write-Output "HTML saved: $($htmlPart.Count) lines"
