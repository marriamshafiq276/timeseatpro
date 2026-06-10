<?php
/**
 * Admin seating-plan generation controller.
 * Runs the seating generator and stores the generated version for review/export.
 */
session_start();

include 'includes/auth_check.php';
include 'includes/config.php';
require_once 'includes/security.php';
require_once 'includes/schema_helpers.php';
require_once 'classes/SeatingGenerator.php';

requireRole('admin', 'login.php');
ensureBuildingRoomSupport($conn);

$generated = false;
$error = "";
$version_id = null;
$version_name = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_seating'])) {
    requireCsrfForPost();

    try {

        $generator = new SeatingGenerator($conn);
        $version_id = $generator->generate();

        // fetch version info for display (SAFE)
        $stmt = $conn->prepare("SELECT version_name FROM seating_versions WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $version_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $version_name = $row['version_name'] ?? null;
        }

        $generated = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include 'includes/header.php';
include 'includes/nav.php';
?>

<div class="bg-gray-50 min-h-screen py-10 px-4">

    <div class="max-w-6xl mx-auto">

        <!-- GENERATE BUTTON -->
        <div class="text-center mb-6">
            <form method="POST">
                <?= csrfInput() ?>
                <button type="submit" name="generate_seating"
                    class="bg-red-600 text-white px-10 py-4 rounded-xl text-lg font-bold shadow-lg hover:bg-red-700">
                    🚀 Generate Seating Plan
                </button>
            </form>
        </div>

        <!-- ERROR -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded text-center mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        
        <!-- SUCCESS (REPLACED WITH VIEW BUTTON) -->
<?php if ($generated): ?>
    <div class="text-center mb-4">

        <a href="view_seating_plan.php?version_id=<?= $version_id ?>"
           class="inline-block bg-green-600 text-white px-8 py-3 rounded-xl text-lg font-bold shadow-lg hover:bg-green-700">
            👁 View Seating Plan
        </a>

    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>




