<?php
$viewFile = $data['viewFile'] ?? '';
$title = $data['title'] ?? 'Dashboard';
$page = $data['page'] ?? 'dashboard';

$currentUser = $data['currentUser'] ?? [];
$userName = htmlspecialchars(strtoupper($currentUser['NAME'] ?? 'Guest'));
$userGender = strtolower($currentUser['GENDER'] ?? '');
$profileImage = htmlspecialchars($currentUser['PROFILE_IMAGE'] ?? '');

$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!DOCTYPE html>
<html lang="en" class="no-js">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>TiffinCraft - <?= htmlspecialchars(ucfirst($title)) ?></title>

	<link rel="stylesheet" href="/assets/css/dashboard.css">

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

	<script>
		document.documentElement.classList.replace('no-js', 'js');
	</script>

	<?php if ($page === 'users'): ?>
		<script src="/assets/js/admin_users.js"></script>
	<?php elseif ($page === 'kitchens'): ?>
		<script src="/assets/js/admin_kitchens.js"></script>
	<?php elseif ($page === 'categories'): ?>
		<script src="/assets/js/admin_categories.js"></script>
	<?php elseif ($page === 'areas'): ?>
		<script src="/assets/js/admin_areas.js"></script>
	<?php elseif ($page === 'reviews'): ?>
		<script src="/assets/js/admin_reviews.js"></script>
	<?php elseif ($page === 'subscriptions'): ?>
		<script src="/assets/js/admin_subscriptions.js"></script>
	<?php elseif ($page === 'transactions'): ?>
		<script src="/assets/js/admin_transactions.js"></script>
	<?php endif; ?>

</head>

<body>
	<div class="topbar">
		<div class="topbar-left">
			<div class="toggle-btn" onclick="toggleSidebar()">
				<i class="fas fa-bars"></i>
			</div>
			<a href="/" class="logo-link"><img src="/assets/images/logo.png" alt="TiffinCraft"></a>
		</div>
		<div class="topbar-right">
			<button class="profile-btn" onclick="toggleDropdown()">
				<span><?= $userName; ?></span>
				<?php if ($profileImage): ?>
					<img src="/uploads/profile/<?= $profileImage; ?>" alt="Profile">
				<?php else: ?>
					<?php if ($userGender == 'male'): ?>
						<img src="/assets/images/M-Avatar.jpg" alt="Profile">
					<?php else: ?>
						<img src="/assets/images/F-Avatar.jpg" alt="Profile">
					<?php endif; ?>
				<?php endif; ?>
			</button>
			<div class="dropdown" id="dropdownMenu">
				<a href="/admin/dashboard/settings"><i class="fas fa-cog"></i> Settings</a>
				<div class="dropdown-divider"></div>
				<a href="/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
			</div>
		</div>
	</div>

	<div class="sidebar" id="sidebar">
		<ul>
			<li class="<?= ($page == 'dashboard') ? 'active' : '' ?>">
				<a href="/admin/dashboard"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
			</li>
			<li class="<?= ($page == 'users') ? 'active' : '' ?>">
				<a href="/admin/dashboard/users"><i class="fas fa-users"></i> <span>Users</span></a>
			</li>
			<li class="<?= ($page == 'kitchens') ? 'active' : '' ?>">
				<a href="/admin/dashboard/kitchens"><i class="fas fa-store"></i> <span>Kitchens</span></a>
			</li>
			<li class="<?= ($page == 'categories') ? 'active' : '' ?>">
				<a href="/admin/dashboard/categories"><i class="fas fa-list"></i> <span>Categories</span></a>
			</li>
			<li class="<?= ($page == 'areas') ? 'active' : '' ?>">
				<a href="/admin/dashboard/areas"><i class="fa-solid fa-earth-americas"></i> <span>Areas</span></a>
			</li>
			<li class="<?= ($page == 'reviews') ? 'active' : '' ?>">
				<a href="/admin/dashboard/reviews"><i class="fas fa-star"></i> <span>Reviews</span></a>
			</li>
			<li class="<?= ($page == 'subscriptions') ? 'active' : '' ?>">
				<a href="/admin/dashboard/subscriptions"><i class="fa-solid fa-cubes"></i> <span>Subscriptions</span></a>
			</li>
			<li class="<?= ($page == 'withdrawals') ? 'active' : '' ?>">
				<a href="/admin/dashboard/withdrawals"><i class="fas fa-money-bill-wave"></i> <span>Withdrawals</span></a>
			</li>
			<li class="<?= ($page == 'refunds') ? 'active' : '' ?>">
				<a href="/admin/dashboard/refunds"><i class="fas fa-undo"></i> <span>Refunds</span></a>
			</li>
			<li class="<?= ($page == 'transactions') ? 'active' : '' ?>">
				<a href="/admin/dashboard/transactions"><i class="fas fa-credit-card"></i> <span>Transactions</span></a>
			</li>
			<li class="<?= ($page == 'settings') ? 'active' : '' ?>">
				<a href="/admin/dashboard/settings"><i class="fa-solid fa-gear"></i> <span>Account Settings</span></a>
			</li>
			<li class="logout-link">
				<a href="/logout"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
			</li>
		</ul>
	</div>

	<div class="main-content" id="main">
		<?php
		if (!empty($viewFile) && file_exists($viewFile)) {
			include $viewFile;
		} else {
			$code = 404;
			$title = 'Page Not Found';
			include BASE_PATH . '/src/views/pages/error.php';
		}
		?>
	</div>

	<script>
		function toggleSidebar() {
			document.getElementById("sidebar").classList.toggle("minimized");
			document.getElementById("main").classList.toggle("shifted");
		}

		function toggleDropdown() {
			document.getElementById("dropdownMenu").classList.toggle("show");
		}

		// Close dropdown if clicked outside
		window.onclick = function(event) {
			if (!event.target.closest('.profile-btn')) {
				document.getElementById("dropdownMenu").classList.remove("show");
			}
		}

		document.addEventListener("DOMContentLoaded", function() {
			// Flash message auto-hide
			document.querySelectorAll(".flash-message").forEach((message) => {
				setTimeout(() => {
					message.style.transition = "all 0.3s ease";
					message.style.opacity = "0";
					message.style.transform = "translateX(100%)";
					setTimeout(() => message.remove(), 300);
				}, 3000);
			});
		});
	</script>
</body>

</html>