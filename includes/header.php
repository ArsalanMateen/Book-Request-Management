<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = $pageTitle ?? 'Book Request System';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?> - BookRequest</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #f5f8ff;
      --bg2: #ffffff;
      --card: #ffffff;
      --accent: #1565c0;
      --accent-2: #00897b;
      --accent2: #00897b;
      --success: #2e7d32;
      --warning: #ef6c00;
      --danger: #c62828;
      --info: #0288d1;
      --text: #0f172a;
      --muted: #64748b;
      --border: #dbe3f0;
      --radius: 16px;
      --shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
      --shadow-soft: 0 8px 20px rgba(15, 23, 42, 0.06);
    }
    body {
      font-family: 'Manrope', sans-serif;
      background:
        radial-gradient(1200px 680px at 8% -12%, rgba(21, 101, 192, 0.16) 0%, rgba(21, 101, 192, 0) 62%),
        radial-gradient(980px 560px at 92% 4%, rgba(0, 137, 123, 0.14) 0%, rgba(0, 137, 123, 0) 60%),
        linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
      color: var(--text);
      min-height: 100vh;
      line-height: 1.5;
    }
    a { color: var(--accent); text-decoration: none; }
    a:hover { text-decoration: underline; }
    h1, h2, h3, .page-title, .nav-brand { font-family: 'Space Grotesk', sans-serif; }

    @keyframes riseIn {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes softPulse {
      0%, 100% { transform: scale(1); opacity: 0.45; }
      50% { transform: scale(1.04); opacity: 0.6; }
    }

    /* NAV */
    nav {
      position: sticky;
      top: 0;
      z-index: 100;
      background: rgba(245, 248, 255, 0.84);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(15, 23, 42, 0.08);
      padding: 0 1.25rem;
    }
    .nav-inner {
      max-width: 1260px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      min-height: 70px;
      gap: 1rem;
    }
    .nav-brand {
      font-size: 1.2rem;
      font-weight: 700;
      color: #0b3a72;
      display: flex;
      align-items: center;
      gap: .5rem;
      letter-spacing: .02em;
    }
    .nav-links {
      display: flex;
      align-items: center;
      gap: .45rem;
      flex-wrap: wrap;
      justify-content: flex-end;
    }
    .nav-links a {
      color: #274060;
      padding: .48rem .95rem;
      border-radius: 999px;
      font-size: .84rem;
      font-weight: 700;
      border: 1px solid transparent;
      transition: all .2s ease;
    }
    .nav-links a:hover,
    .nav-links a.active {
      color: #0d2744;
      background: rgba(21, 101, 192, 0.12);
      border-color: rgba(21, 101, 192, 0.2);
      text-decoration: none;
    }
    .nav-links .btn-nav {
      background: linear-gradient(135deg, #1565c0 0%, #0277bd 100%);
      color: #fff !important;
      border-color: transparent;
      box-shadow: 0 6px 16px rgba(2, 119, 189, 0.25);
    }
    .nav-links .btn-nav:hover {
      filter: brightness(1.05);
      transform: translateY(-1px);
    }
    .nav-links .btn-danger-sm {
      background: #fff;
      color: var(--danger) !important;
      border: 1px solid rgba(198, 40, 40, .24);
    }
    .nav-links .btn-danger-sm:hover {
      background: rgba(198, 40, 40, .1);
    }

    /* MAIN WRAPPER */
    .main-wrap,
    .container {
      width: min(1260px, 100% - 2rem);
      margin: 1.4rem auto 0;
      padding: 0;
      animation: riseIn .5s ease both;
    }

    .main-wrap::before,
    .container::before {
      content: "";
      position: fixed;
      right: -130px;
      bottom: -120px;
      width: 340px;
      height: 340px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(0, 137, 123, 0.2), rgba(0, 137, 123, 0));
      pointer-events: none;
      animation: softPulse 7s ease-in-out infinite;
      z-index: -1;
    }

    /* ALERTS */
    .alert { padding: .95rem 1.15rem; border-radius: 12px; font-size: .9rem; margin-bottom: 1rem; border: 1px solid; font-weight: 600; }
    .alert-success { background: rgba(46,125,50,.11); border-color: rgba(46,125,50,.25); color: #256328; }
    .alert-danger  { background: rgba(198,40,40,.11); border-color: rgba(198,40,40,.25); color: #8f1c1c; }
    .alert-error   { background: rgba(198,40,40,.11); border-color: rgba(198,40,40,.25); color: #8f1c1c; }
    .alert-info    { background: rgba(2,136,209,.11); border-color: rgba(2,136,209,.24); color: #055f90; }
    .alert-warning { background: rgba(239,108,0,.11); border-color: rgba(239,108,0,.24); color: #9a4500; }

    .flex-between {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: .75rem;
      margin-bottom: 1rem;
      flex-wrap: wrap;
    }

    /* CARDS */
    .card {
      background: var(--card);
      border: 1px solid rgba(15, 23, 42, 0.08);
      border-radius: var(--radius);
      padding: 1.45rem;
      box-shadow: var(--shadow-soft);
      transition: transform .24s ease, box-shadow .24s ease;
      animation: riseIn .52s ease both;
    }
    .card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow);
    }

    /* TABLES */
    .table-wrap {
      overflow-x: auto;
      border-radius: 14px;
      border: 1px solid rgba(15, 23, 42, 0.09);
      background: #fff;
      box-shadow: var(--shadow-soft);
    }
    table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    th {
      background: #eef5ff;
      color: #355070;
      font-weight: 800;
      text-transform: uppercase;
      font-size: .72rem;
      letter-spacing: .08em;
      padding: .84rem .95rem;
      text-align: left;
      border-bottom: 1px solid rgba(15, 23, 42, 0.09);
      white-space: nowrap;
    }
    td { padding: .88rem .95rem; border-bottom: 1px solid rgba(15, 23, 42, 0.08); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #f8fbff; }

    /* FORMS */
    .form-group { margin-bottom: 1rem; }
    label { display: block; font-size: .75rem; font-weight: 800; color: #3c5a7d; text-transform: uppercase; letter-spacing: .08em; margin-bottom: .45rem; }
    input[type=text], input[type=email], input[type=password], select, textarea {
      width: 100%;
      background: #fbfdff;
      border: 1px solid #ccd9eb;
      border-radius: 12px;
      color: var(--text);
      font-family: 'Manrope', sans-serif;
      font-size: .92rem;
      padding: .72rem .9rem;
      outline: none;
      transition: all .2s;
    }
    input:focus, select:focus, textarea:focus {
      border-color: #1d6fcb;
      box-shadow: 0 0 0 4px rgba(29, 111, 203, 0.14);
      background: #fff;
    }
    input[readonly] { opacity: .6; cursor: not-allowed; }
    select option { background: #fff; }

    .select-compact {
      width: auto;
      min-width: 150px;
      padding: .48rem .7rem;
      border-radius: 10px;
      font-size: .8rem;
      font-weight: 700;
      background: #f6f9ff;
      border-color: #bfd0e6;
      color: #1f3a57;
    }

    /* BUTTONS */
    .btn { display: inline-flex; align-items: center; gap: .35rem; padding: .62rem 1.3rem; border-radius: 12px; font-family: 'Manrope',sans-serif; font-size: .85rem; font-weight: 800; cursor: pointer; border: 1px solid transparent; transition: all .2s; text-decoration: none !important; }
    .btn-primary { background: linear-gradient(135deg, #1565c0, #0277bd); color: #fff; box-shadow: 0 10px 20px rgba(21, 101, 192, 0.24); }
    .btn-primary:hover { transform: translateY(-1px); filter: brightness(1.04); }
    .btn-success { background: var(--success); color: #fff; }
    .btn-success:hover { background: #256d2a; }
    .btn-danger { background: rgba(198,40,40,.1); color: var(--danger); border: 1px solid rgba(198,40,40,.28); }
    .btn-danger:hover { background: rgba(198,40,40,.16); color: #791212; }
    .btn-warning { background: rgba(239,108,0,.1); color: #9a4500; border: 1px solid rgba(239,108,0,.3); }
    .btn-warning:hover { background: rgba(239,108,0,.16); }
    .btn-secondary { background: #ffffff; color: #20425f; border: 1px solid rgba(32,66,95,.2); }
    .btn-secondary:hover { background: #f1f7ff; }
    .btn-sm { padding: .35rem .85rem; font-size: .8rem; }
    .btn-block { width: 100%; justify-content: center; padding: .78rem; font-size: .93rem; }

    /* BADGES */
    .badge { display: inline-block; padding: .25rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .badge-pending    { background: rgba(239,108,0,.14); color: #9a4500; }
    .badge-progress   { background: rgba(2,136,209,.14); color: #075d8b; }
    .badge-completed  { background: rgba(46,125,50,.14); color: #256328; }
    .badge-admin      { background: rgba(21,101,192,.14); color: #0b4b95; }
    .badge-superadmin { background: rgba(198,40,40,.14); color: #901919; }

    /* STAT CARDS */
    .stats-grid,
    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: .95rem;
      margin-bottom: 1.4rem;
    }
    .stat-card {
      position: relative;
      overflow: hidden;
      background: linear-gradient(155deg, rgba(255,255,255,0.96), rgba(248,252,255,0.92));
      border: 1px solid rgba(32,66,95,.12);
      border-radius: var(--radius);
      padding: 1.15rem;
      text-align: left;
      box-shadow: var(--shadow-soft);
      animation: riseIn .52s ease both;
    }
    .stat-card::after {
      content: "";
      position: absolute;
      right: -44px;
      top: -44px;
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(21, 101, 192, .2), rgba(21, 101, 192, 0));
    }
    .stat-num { font-size: 2rem; font-weight: 800; line-height: 1; margin-bottom: .38rem; letter-spacing: -.02em; }
    .stat-label { font-size: .74rem; color: #5b6d84; text-transform: uppercase; letter-spacing: .11em; font-weight: 800; }

    /* PAGE HEADER */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: .9rem;
      margin-bottom: 1.1rem;
      padding: 1.25rem;
      border-radius: 18px;
      border: 1px solid rgba(32,66,95,.1);
      background: linear-gradient(145deg, rgba(255,255,255,.95), rgba(241,248,255,.86));
      box-shadow: var(--shadow-soft);
      animation: riseIn .45s ease both;
    }
    .page-title { font-size: clamp(1.22rem, 2.8vw, 1.7rem); font-weight: 700; letter-spacing: -.01em; }
    .page-subtitle { color: #546b86; font-size: .9rem; margin-top: .2rem; }

    @media (max-width: 768px) {
      nav { padding-inline: .75rem; }
      .main-wrap,
      .container { width: min(1260px, 100% - 1rem); }
      .card { padding: 1rem; }
      .page-header { padding: 1rem; }
      .nav-links { gap: .3rem; }
      .nav-links a { padding: .45rem .72rem; font-size: .78rem; }
      .btn { width: 100%; justify-content: center; }
      .page-header .btn { width: auto; }
      .stat-num { font-size: 1.7rem; }
    }
  </style>
</head>
<body>
