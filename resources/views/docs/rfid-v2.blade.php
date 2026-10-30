<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dokumentasi REST API RFIDv2 — Jagat Absen</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f8fafc;
      --card-bg: #ffffff;
      --border: #e2e8f0;
      --text: #0f172a;
      --text-muted: #64748b;
      --accent: #10b981;
      --accent-hover: #059669;
      --code-bg: #0f172a;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background-color: var(--bg);
      color: var(--text);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      min-height: 100vh;
      line-height: 1.65;
    }

    header {
      padding: 1.15rem 2rem;
      border-bottom: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(12px);
      position: sticky;
      top: 0;
      z-index: 50;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .brand-icon {
      width: 38px;
      height: 38px;
      background: linear-gradient(135deg, #10b981, #06b6d4);
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
    }

    .brand h1 {
      font-size: 1.15rem;
      font-weight: 700;
      color: #0f172a;
    }

    .brand-badge {
      font-size: 0.7rem;
      padding: 0.2rem 0.55rem;
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      color: #059669;
      border-radius: 999px;
      font-family: 'JetBrains Mono', monospace;
      font-weight: 600;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      font-size: 0.825rem;
      font-weight: 600;
      padding: 0.45rem 0.85rem;
      border-radius: 8px;
      text-decoration: none;
      transition: all 0.2s;
    }

    .btn-simulator {
      background: linear-gradient(135deg, #10b981, #059669);
      color: #fff;
      box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
    }
    .btn-simulator:hover {
      filter: brightness(1.08);
      transform: translateY(-1px);
    }

    .btn-raw {
      background: #f1f5f9;
      border: 1px solid var(--border);
      color: #475569;
    }
    .btn-raw:hover {
      color: #0f172a;
      background: #e2e8f0;
    }

    .container {
      max-width: 960px;
      margin: 2.25rem auto 3.5rem;
      padding: 0 1.5rem;
    }

    .doc-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 2.75rem 3.25rem;
      box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.02);
    }

    @media (max-width: 768px) {
      .doc-card { padding: 1.5rem 1.25rem; }
    }

    /* Markdown Rendering Styles (Light Mode) */
    .markdown-body h1 {
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 1.25rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border);
      color: #0f172a;
    }

    .markdown-body h2 {
      font-size: 1.35rem;
      font-weight: 700;
      margin-top: 2.25rem;
      margin-bottom: 1rem;
      padding-bottom: 0.45rem;
      border-bottom: 1px solid #f1f5f9;
      color: #0284c7;
    }

    .markdown-body h3 {
      font-size: 1.1rem;
      font-weight: 600;
      margin-top: 1.65rem;
      margin-bottom: 0.75rem;
      color: #0f172a;
    }

    .markdown-body h4 {
      font-size: 0.95rem;
      font-weight: 600;
      margin-top: 1.35rem;
      margin-bottom: 0.5rem;
      color: #059669;
    }

    .markdown-body p {
      margin-bottom: 1rem;
      color: #334155;
    }

    .markdown-body ul, .markdown-body ol {
      margin-bottom: 1.25rem;
      padding-left: 1.5rem;
      color: #334155;
    }

    .markdown-body li {
      margin-bottom: 0.45rem;
    }

    .markdown-body hr {
      border: 0;
      border-top: 1px solid var(--border);
      margin: 2.25rem 0;
    }

    .markdown-body pre {
      background: var(--code-bg);
      border: 1px solid #1e293b;
      border-radius: 10px;
      padding: 1.15rem;
      margin: 1rem 0 1.5rem;
      overflow-x: auto;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.85rem;
      line-height: 1.55;
      color: #f8fafc;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .markdown-body code {
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.85em;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      padding: 0.15em 0.4em;
      border-radius: 5px;
      color: #0284c7;
    }

    .markdown-body pre code {
      background: transparent;
      border: none;
      padding: 0;
      color: inherit;
    }

    .markdown-body table {
      width: 100%;
      border-collapse: collapse;
      margin: 1.35rem 0 1.75rem;
      font-size: 0.85rem;
    }

    .markdown-body th {
      background: #f8fafc;
      color: #0f172a;
      font-weight: 600;
      text-align: left;
      padding: 0.75rem 1rem;
      border: 1px solid var(--border);
    }

    .markdown-body td {
      padding: 0.75rem 1rem;
      border: 1px solid var(--border);
      color: #334155;
    }

    .markdown-body tr:nth-child(even) td {
      background: #fcfcfd;
    }

    .markdown-body blockquote {
      border-left: 4px solid var(--accent);
      background: #f0fdf4;
      padding: 0.85rem 1.15rem;
      margin: 1.25rem 0;
      border-radius: 0 8px 8px 0;
      color: #166534;
      font-size: 0.9rem;
    }

    .markdown-body a {
      color: #0284c7;
      text-decoration: none;
      font-weight: 500;
    }
    .markdown-body a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <header>
    <div class="brand">
      <div class="brand-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
        </svg>
      </div>
      <div>
        <h1>Dokumentasi API RFIDv2</h1>
      </div>
      <span class="brand-badge">REST API SPEC</span>
    </div>

    <div class="header-actions">
      <a href="/simulator" class="btn btn-simulator">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
          <polygon points="5 3 19 12 5 21 5 3"></polygon>
        </svg>
        Buka Device Simulator
      </a>
    </div>
  </header>

  <main class="container">
    <div class="doc-card">
      <article class="markdown-body">
        {!! $htmlContent !!}
      </article>
    </div>
  </main>

</body>
</html>
