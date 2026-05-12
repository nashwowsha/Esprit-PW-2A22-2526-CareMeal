<?php

if (!function_exists('caremeal_pdf_escape')) {
    function caremeal_pdf_escape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('caremeal_load_dompdf')) {
    function caremeal_load_dompdf()
    {
        if (class_exists('\Dompdf\Dompdf')) {
            return true;
        }

        $autoloadCandidates = [
            __DIR__ . '/../vendor/autoload.php',
            dirname(__DIR__) . '/vendor/autoload.php',
            'C:/xampp/htdocs/vendor/autoload.php',
        ];

        foreach ($autoloadCandidates as $autoload) {
            if (is_file($autoload)) {
                require_once $autoload;
                if (class_exists('\Dompdf\Dompdf')) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('caremeal_pdf_missing_dependency_page')) {
    function caremeal_pdf_missing_dependency_page()
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Dompdf manquant</title></head><body style="font-family:Arial,sans-serif;padding:24px;">';
        echo '<h2>Export PDF indisponible</h2>';
        echo '<p>Dompdf n\'est pas installe dans ce projet.</p>';
        echo '<p>Installe-le puis reessaie:</p>';
        echo '<pre style="background:#f5f5f5;padding:12px;border:1px solid #ddd;">composer require dompdf/dompdf</pre>';
        echo '</body></html>';
        exit;
    }
}

if (!function_exists('caremeal_stream_table_pdf')) {
    function caremeal_stream_table_pdf($fileName, $title, array $headers, array $rows, $orientation = 'landscape')
    {
        if (!caremeal_load_dompdf()) {
            caremeal_pdf_missing_dependency_page();
        }

        $title = trim((string)$title);
        if ($title === '') {
            $title = 'Export PDF';
        }

        $theadHtml = '';
        foreach ($headers as $header) {
            $theadHtml .= '<th>' . caremeal_pdf_escape($header) . '</th>';
        }

        $tbodyHtml = '';
        if (empty($rows)) {
            $tbodyHtml .= '<tr><td colspan="' . max(1, count($headers)) . '">Aucune donnee.</td></tr>';
        } else {
            foreach ($rows as $row) {
                $tbodyHtml .= '<tr>';
                foreach ($row as $cell) {
                    $tbodyHtml .= '<td>' . caremeal_pdf_escape($cell) . '</td>';
                }
                $tbodyHtml .= '</tr>';
            }
        }

        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; }
    h2 { margin: 0 0 8px 0; font-size: 16px; }
    .meta { margin: 0 0 12px 0; color: #555; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #cfcfcf; padding: 6px; vertical-align: top; word-wrap: break-word; }
    th { background: #f2f2f2; text-align: left; }
  </style>
</head>
<body>
  <h2>' . caremeal_pdf_escape($title) . '</h2>
  <p class="meta">Genere le ' . date('Y-m-d H:i:s') . '</p>
  <table>
    <thead><tr>' . $theadHtml . '</tr></thead>
    <tbody>' . $tbodyHtml . '</tbody>
  </table>
</body>
</html>';

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $orientation === 'portrait' ? 'portrait' : 'landscape');
        $dompdf->render();
        $dompdf->stream((string)$fileName, ['Attachment' => true]);
        exit;
    }
}


