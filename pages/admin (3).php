<?php
/**
 * HustleKingdom — Admin Panel
 * Withdrawal request management. Access controlled by ADMIN_SECRET env var.
 * URL: /admin?secret=YOUR_ADMIN_SECRET
 */
defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';

// ── AUTH: secret-in-URL, stored in session after first verification ──────────
session_name(SESSION_NAME);
session_start();

$adminSecret = _env('ADMIN_SECRET', '');
if (empty($adminSecret)) {
    http_response_code(503);
    die('<h2>Admin not configured.</h2><p>Set ADMIN_SECRET in your .env file.</p>');
}

if (isset($_GET['secret'])) {
    if (hash_equals($adminSecret, $_GET['secret'])) {
        $_SESSION['hk_admin'] = true;
        header('Location: /admin');
        exit;
    } else {
        http_response_code(403);
        die('<h2>Invalid secret.</h2>');
    }
}

if (empty($_SESSION['hk_admin'])) {
    http_response_code(401);
    die('<h2>Unauthorized.</h2><p>Append <code>?secret=YOUR_ADMIN_SECRET</code> to the URL.</p>');
}

// ── ACTIONS ──────────────────────────────────────────────────────────────────
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'mark_paid') {
            DB::run(
                "UPDATE withdrawal_requests SET status='paid', resolved_at=NOW() WHERE id=:id AND status IN ('pending','processing')",
                [':id' => $id]
            );
            $flash = ['type' => 'ok', 'msg' => "Request #$id marked as paid."];
        } elseif ($action === 'mark_processing') {
            DB::run(
                "UPDATE withdrawal_requests SET status='processing' WHERE id=:id AND status='pending'",
                [':id' => $id]
            );
            $flash = ['type' => 'ok', 'msg' => "Request #$id marked as processing."];
        } elseif ($action === 'reject') {
            $note = trim(strip_tags($_POST['note'] ?? ''));
            DB::run(
                "UPDATE withdrawal_requests SET status='rejected', admin_note=:note, resolved_at=NOW() WHERE id=:id AND status IN ('pending','processing')",
                [':id' => $id, ':note' => $note ?: 'Rejected by admin.']
            );
            $flash = ['type' => 'warn', 'msg' => "Request #$id rejected."];
        }
    }
    header('Location: /admin');
    exit;
}

// ── DATA ─────────────────────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? 'pending';
$validStatuses = ['pending','processing','paid','rejected','all'];
if (!in_array($filterStatus, $validStatuses, true)) $filterStatus = 'pending';

$where = $filterStatus === 'all' ? '1' : "wr.status = '$filterStatus'";

$requests = DB::query(
    "SELECT wr.*, u.name as user_name, u.email as user_email, u.ref_code,
            (SELECT COUNT(*) FROM referrals r2
             JOIN users ur ON ur.id = r2.referred_id
             WHERE r2.referrer_id = u.id AND ur.is_pro = 1
               AND (ur.pro_expires_at IS NULL OR ur.pro_expires_at > NOW())) as paid_refs,
            (SELECT COALESCE(SUM(wr2.amount),0) FROM withdrawal_requests wr2
             WHERE wr2.user_id = u.id AND wr2.status IN ('paid')) as total_paid_out
     FROM withdrawal_requests wr
     JOIN users u ON u.id = wr.user_id
     WHERE $where
     ORDER BY wr.requested_at DESC"
);

$counts = DB::query(
    "SELECT status, COUNT(*) as n FROM withdrawal_requests GROUP BY status"
);
$cnt = ['pending'=>0,'processing'=>0,'paid'=>0,'rejected'=>0];
foreach ($counts as $c) $cnt[$c['status']] = (int)$c['n'];
$cnt['all'] = array_sum($cnt);

$totalPending = DB::one("SELECT COALESCE(SUM(amount),0) as t FROM withdrawal_requests WHERE status='pending'")['t'] ?? 0;
$totalProcessing = DB::one("SELECT COALESCE(SUM(amount),0) as t FROM withdrawal_requests WHERE status='processing'")['t'] ?? 0;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Admin — Withdrawals · Hustle Kingdom</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f5f5f3;color:#0d0d0c;font-size:14px;}
a{color:#2055d4;text-decoration:none;}
a:hover{text-decoration:underline;}

.topbar{background:#0d6e3c;color:#fff;padding:0 24px;height:52px;display:flex;align-items:center;justify-content:space-between;}
.topbar-brand{font-weight:800;font-size:16px;letter-spacing:-.02em;}
.topbar-brand span{opacity:.6;font-weight:400;font-size:13px;margin-left:10px;}
.topbar-logout{font-size:12px;color:rgba(255,255,255,.7);cursor:pointer;background:none;border:1px solid rgba(255,255,255,.3);padding:4px 12px;border-radius:6px;}
.topbar-logout:hover{background:rgba(255,255,255,.1);}

.wrap{max-width:1100px;margin:0 auto;padding:28px 20px;}

.flash{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px;font-weight:600;}
.flash.ok{background:#ebf7f1;border:1px solid #b2e0c8;color:#0d6e3c;}
.flash.warn{background:#fdf0eb;border:1px solid #f0c0a0;color:#a03000;}

.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;}
.stat{background:#fff;border:1px solid #e6e6e0;border-radius:10px;padding:16px 18px;}
.stat-n{font-size:24px;font-weight:800;font-family:'Bricolage Grotesque',sans-serif;}
.stat-l{font-size:11px;color:#9a9a92;font-weight:600;margin-top:2px;}
.stat.amber .stat-n{color:#854f0b;}
.stat.blue  .stat-n{color:#2055d4;}
.stat.green .stat-n{color:#0d6e3c;}
.stat.red   .stat-n{color:#a32d2d;}

.tab-row{display:flex;gap:4px;margin-bottom:18px;border-bottom:2px solid #e6e6e0;padding-bottom:0;}
.tab{padding:8px 16px;font-size:13px;font-weight:700;border-radius:8px 8px 0 0;text-decoration:none;color:#9a9a92;display:flex;align-items:center;gap:5px;}
.tab:hover{color:#0d0d0c;text-decoration:none;}
.tab.active{background:#fff;color:#0d0d0c;border:2px solid #e6e6e0;border-bottom:2px solid #fff;margin-bottom:-2px;}
.tab-badge{background:#e6e6e0;color:#4a4a45;border-radius:20px;padding:1px 7px;font-size:11px;}
.tab.active .tab-badge{background:#0d6e3c;color:#fff;}

table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e6e6e0;}
th{background:#f8f8f6;text-align:left;padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9a9a92;border-bottom:1px solid #e6e6e0;}
td{padding:12px 14px;border-bottom:1px solid #f1f1ee;vertical-align:top;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafaf8;}

.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-pending{background:#faeeda;color:#854f0b;}
.badge-processing{background:#edf2fd;color:#2055d4;}
.badge-paid{background:#ebf7f1;color:#0d6e3c;}
.badge-rejected{background:#fdf0f0;color:#a32d2d;}

.user-cell{display:flex;flex-direction:column;gap:2px;}
.user-name{font-weight:700;font-size:13px;}
.user-email{font-size:11px;color:#9a9a92;}

.bank-cell{font-size:12px;line-height:1.6;}
.acct-num{font-family:monospace;font-weight:700;font-size:13px;letter-spacing:.05em;}

.amount-cell{font-family:monospace;font-weight:800;font-size:15px;color:#0d6e3c;}

.actions{display:flex;flex-direction:column;gap:6px;min-width:130px;}
.btn{padding:6px 12px;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;border:none;width:100%;text-align:center;}
.btn-pay{background:#16a05a;color:#fff;}
.btn-pay:hover{background:#128f4f;}
.btn-proc{background:#edf2fd;color:#2055d4;border:1px solid #c4d4f8;}
.btn-proc:hover{background:#d8e6fb;}
.btn-rej{background:#fdf0f0;color:#a32d2d;border:1px solid #f0c0c0;}
.btn-rej:hover{background:#fae0e0;}

.empty{text-align:center;padding:48px;color:#9a9a92;}
.empty-icon{font-size:32px;margin-bottom:8px;}

.context-row{font-size:11px;color:#9a9a92;margin-top:3px;}

@media(max-width:800px){
  .stats-row{grid-template-columns:repeat(2,1fr);}
  table{font-size:12px;}
  th,td{padding:8px 10px;}
  .actions{flex-direction:row;flex-wrap:wrap;}
  .btn{width:auto;flex:1;}
}
</style>
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">👑 HustleKingdom <span>Admin · Withdrawals</span></div>
  <form method="POST" style="margin:0;">
    <input type="hidden" name="action" value="__logout"/>
    <button class="topbar-logout" onclick="<?php $_SESSION['hk_admin']=false; ?>">
      <a href="/admin?logout=1" style="color:rgba(255,255,255,.7);text-decoration:none;">Log out</a>
    </button>
  </form>
</div>

<?php if (isset($_GET['logout'])): <?php $_SESSION['hk_admin']=false; header('Location:/'); exit; endif; ?>

<div class="wrap">

  <?php if ($flash): ?>
    <div class="flash <?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <div class="stats-row">
    <div class="stat amber">
      <div class="stat-n"><?= $cnt['pending'] ?></div>
      <div class="stat-l">PENDING</div>
      <div style="font-size:12px;color:#854f0b;margin-top:4px;">₦<?= number_format($totalPending) ?></div>
    </div>
    <div class="stat blue">
      <div class="stat-n"><?= $cnt['processing'] ?></div>
      <div class="stat-l">PROCESSING</div>
      <div style="font-size:12px;color:#2055d4;margin-top:4px;">₦<?= number_format($totalProcessing) ?></div>
    </div>
    <div class="stat green">
      <div class="stat-n"><?= $cnt['paid'] ?></div>
      <div class="stat-l">PAID OUT</div>
    </div>
    <div class="stat red">
      <div class="stat-n"><?= $cnt['rejected'] ?></div>
      <div class="stat-l">REJECTED</div>
    </div>
  </div>

  <div class="tab-row">
    <?php foreach ([
      'pending'    => 'Pending',
      'processing' => 'Processing',
      'paid'       => 'Paid',
      'rejected'   => 'Rejected',
      'all'        => 'All',
    ] as $s => $label): ?>
      <a href="/admin?status=<?= $s ?>" class="tab <?= $filterStatus===$s?'active':'' ?>">
        <?= $label ?>
        <span class="tab-badge"><?= $cnt[$s] ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($requests)): ?>
    <div class="empty">
      <div class="empty-icon">✅</div>
      No <?= $filterStatus === 'all' ? '' : $filterStatus ?> requests.
    </div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>User</th>
        <th>Bank Details</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Requested</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
      <tr>
        <td style="color:#9a9a92;font-size:12px;"><?= $r['id'] ?></td>
        <td>
          <div class="user-cell">
            <div class="user-name"><?= htmlspecialchars($r['user_name']) ?></div>
            <div class="user-email"><?= htmlspecialchars($r['user_email']) ?></div>
            <div class="context-row">
              <?= (int)$r['paid_refs'] ?> paid ref<?= $r['paid_refs']!=1?'s':'' ?> ·
              ₦<?= number_format((int)($r['paid_refs'] * 500)) ?> total earned ·
              ₦<?= number_format((int)$r['total_paid_out']) ?> paid out
            </div>
          </div>
        </td>
        <td>
          <div class="bank-cell">
            <strong><?= htmlspecialchars($r['bank_name']) ?></strong><br/>
            <?= htmlspecialchars($r['account_name']) ?><br/>
            <span class="acct-num"><?= htmlspecialchars($r['account_number']) ?></span>
          </div>
        </td>
        <td><div class="amount-cell">₦<?= number_format($r['amount']) ?></div></td>
        <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
          <?php if ($r['admin_note']): ?>
            <div style="font-size:11px;color:#9a9a92;margin-top:3px;"><?= htmlspecialchars($r['admin_note']) ?></div>
          <?php endif; ?>
        </td>
        <td style="font-size:12px;color:#4a4a45;white-space:nowrap;">
          <?= date('d M Y', strtotime($r['requested_at'])) ?><br/>
          <span style="color:#9a9a92;"><?= date('H:i', strtotime($r['requested_at'])) ?></span>
          <?php if ($r['resolved_at']): ?>
            <br/><span style="color:#9a9a92;">→ <?= date('d M Y', strtotime($r['resolved_at'])) ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?php if (in_array($r['status'], ['pending','processing'])): ?>
          <div class="actions">
            <?php if ($r['status'] === 'pending'): ?>
            <form method="POST" style="margin:0;">
              <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
              <input type="hidden" name="action" value="mark_processing"/>
              <button class="btn btn-proc" type="submit">Mark Processing</button>
            </form>
            <?php endif; ?>
            <form method="POST" style="margin:0;">
              <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
              <input type="hidden" name="action" value="mark_paid"/>
              <button class="btn btn-pay" type="submit"
                onclick="return confirm('Confirm ₦<?= number_format($r['amount']) ?> paid to <?= htmlspecialchars(addslashes($r['account_name'])) ?> (<?= htmlspecialchars($r['account_number']) ?>)?')">
                ✅ Mark Paid
              </button>
            </form>
            <form method="POST" style="margin:0;" onsubmit="return addNote(this)">
              <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
              <input type="hidden" name="action" value="reject"/>
              <input type="hidden" name="note" class="reject-note" value=""/>
              <button class="btn btn-rej" type="submit">Reject</button>
            </form>
          </div>
          <?php else: ?>
            <span style="font-size:12px;color:#9a9a92;">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

</div>
<script>
function addNote(form) {
  const note = prompt('Rejection reason (optional):') ?? '';
  form.querySelector('.reject-note').value = note;
  return confirm('Reject this request?');
}
</script>
</body>
</html>
