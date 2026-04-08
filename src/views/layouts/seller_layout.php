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
	<link rel="stylesheet" href="/assets/css/seller-dashboard.css">

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

	<script>
		document.documentElement.classList.replace('no-js', 'js');
	</script>

	<?php if ($page === 'dashboard'): ?>
		<script src="/assets/js/seller_dashboard.js"></script>
	<?php elseif ($page === 'menu-items'): ?>
		<script src="/assets/js/seller_menu-items.js"></script>
	<?php elseif ($page === 'orders'): ?>
		<script src="/assets/js/seller_orders.js"></script>
	<?php elseif ($page === 'reviews'): ?>
		<!-- <script src="/assets/js/seller_reviews.js"></script> -->
	<?php elseif ($page === 'areas'): ?>
		<script src="/assets/js/admin_areas.js"></script>
	<?php elseif ($page === 'subscriptions'): ?>
		<script src="/assets/js/admin_subscriptions.js"></script>
	<?php elseif ($page === 'transactions'): ?>
		<script src="/assets/js/admin_transactions.js"></script>
	<?php endif; ?>

</head>

<body>
	<main>
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
					<a href="/business/dashboard/settings"><i class="fas fa-cog"></i> Settings</a>
					<div class="dropdown-divider"></div>
					<a href="/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
				</div>
			</div>
		</div>

		<div class="sidebar" id="sidebar">
			<ul>
				<li class="<?= ($page == 'dashboard') ? 'active' : '' ?>">
					<a href="/business/dashboard"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
				</li>
				<li class="<?= ($page == 'menu-items') ? 'active' : '' ?>">
					<a href="/business/dashboard/menu-items"><i class="fas fa-utensils"></i> <span>Menu Items</span></a>
				</li>
				<li class="<?= ($page == 'orders') ? 'active' : '' ?>">
					<a href="/business/dashboard/orders"><i class="fas fa-receipt"></i> <span>Orders</span></a>
				</li>
				<li class="<?= ($page == 'reviews') ? 'active' : '' ?>">
					<a href="/business/dashboard/reviews"><i class="fas fa-star"></i> <span>Reviews</span></a>
				</li>
				<li class="<?= ($page == 'service-areas') ? 'active' : '' ?>">
					<a href="/business/dashboard/service-areas"><i class="fas fa-map-marker-alt"></i> <span>Service Areas</span></a>
				</li>
				<li class="<?= ($page == 'subscriptions') ? 'active' : '' ?>">
					<a href="/business/dashboard/subscriptions"><i class="fas fa-box-open"></i> <span>Subscriptions</span></a>
				</li>
				<li class="<?= ($page == 'withdrawals') ? 'active' : '' ?>">
					<a href="/business/dashboard/withdrawals"><i class="fas fa-wallet"></i> <span>Withdrawals</span></a>
				</li>
				<li class="<?= ($page == 'analytics') ? 'active' : '' ?>">
					<a href="/business/dashboard/analytics"><i class="fas fa-chart-line"></i> <span>Analytics</span></a>
				</li>
				<li class="<?= ($page == 'settings') ? 'active' : '' ?>">
					<a href="/business/dashboard/settings"><i class="fas fa-user-cog"></i> <span>Settings</span></a>
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
	</main>
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