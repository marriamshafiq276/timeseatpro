<?php
$pageTitle = 'Contact | Academic Scheduling & Seating Suite';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/config.php';

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name !== '' && $email !== '' && $message !== '') {
        $stmt = $conn->prepare("INSERT INTO contacts (name, email, message, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $stmt->bind_param('sssss', $name, $email, $message, $ip, $ua);

        $ok = $stmt->execute();
        $stmt->close();

        $submissionSuccess = $ok ? true : false;
        $submissionError = $ok ? null : 'Unable to save your message. Please try again later.';
    } else {
        $submissionError = 'Please complete all fields.';
    }
}

include 'includes/public_header.php';
?>

<main class="bg-emerald-50/40">

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

        <div class="grid gap-10 lg:grid-cols-2 items-start">

            <!-- LEFT -->
            <div class="space-y-6">

                <p class="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-emerald-800">
                    Contact
                </p>

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-emerald-950 leading-tight">
                    Professional support for your academic portal
                </h1>

                <p class="text-base sm:text-lg leading-7 text-slate-700 max-w-2xl">
                    Need help with login approval, timetable management, student records, or seating plan support?
                </p>

                <!-- INFO CARDS -->
                <div class="grid gap-4 sm:grid-cols-2">

                    <div class="rounded-2xl border border-emerald-200 bg-white p-5 sm:p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase text-emerald-700">Phone</p>
                        <p class="mt-2 text-lg font-bold text-emerald-950 break-words">
                            +92419200161-70
                        </p>
                        <p class="mt-1 text-sm text-slate-600">Mon–Fri, 8 AM – 4 PM</p>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-white p-5 sm:p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase text-emerald-700">Emails</p>

                        <div class="mt-2 space-y-2 text-sm break-words">

                            <p class="font-semibold text-emerald-950">Admissions</p>
                            <p>admissionsupport@uaf.edu.pk</p>

                            <p class="font-semibold text-emerald-950 mt-2">Examinations</p>
                            <p>controller.examinations@uaf.edu.pk</p>

                            <p class="font-semibold text-emerald-950 mt-2">IT Center</p>
                            <p>director.it@uaf.edu.pk</p>

                        </div>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-white p-5 sm:p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase text-emerald-700">Working Hours</p>
                        <p class="mt-2 text-base sm:text-lg font-bold text-emerald-950">
                            Monday–Friday
                        </p>
                        <p class="text-sm text-slate-600">8:00 AM – 4:00 PM</p>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-white p-5 sm:p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase text-emerald-700">Office</p>
                        <p class="mt-2 text-base sm:text-lg font-bold text-emerald-950">
                            Administration Block
                        </p>
                        <p class="text-sm text-slate-600">University Campus</p>
                    </div>

                </div>
            </div>

            <!-- RIGHT FORM -->
            <div class="rounded-2xl sm:rounded-3xl border border-emerald-200 bg-white p-6 sm:p-8 lg:p-10 shadow-xl w-full">

                <h2 class="text-xl sm:text-2xl font-extrabold text-emerald-900">
                    Send us a message
                </h2>

                <p class="mt-2 text-sm text-slate-600">
                    We will respond as soon as possible.
                </p>

                <?php if (!empty($submissionSuccess)): ?>
                    <div class="mt-4 rounded-xl bg-green-50 border border-green-200 p-3 sm:p-4 text-green-800 text-sm">
                        Message sent successfully.
                    </div>
                <?php elseif (!empty($submissionError)): ?>
                    <div class="mt-4 rounded-xl bg-red-50 border border-red-200 p-3 sm:p-4 text-red-800 text-sm">
                        <?= htmlspecialchars($submissionError) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="mt-6 space-y-4 sm:space-y-5">

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Full name</label>
                        <input type="text" name="name" required
                               class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Email</label>
                        <input type="email" name="email" required
                               class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Message</label>
                        <textarea name="message" rows="5" required
                                  class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100"></textarea>
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl bg-emerald-900 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800 transition">
                        Send Message
                    </button>

                </form>
            </div>

        </div>
    </section>

    <!-- BOTTOM SECTION -->
    <section class="border-t border-emerald-200 bg-white">

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

            <h2 class="text-2xl sm:text-3xl font-extrabold text-emerald-950">
                How we help
            </h2>

            <p class="mt-2 text-sm sm:text-lg text-slate-600 max-w-2xl">
                Fast and accurate academic support services.
            </p>

            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">

                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 sm:p-7">
                    <h3 class="text-lg sm:text-xl font-bold text-emerald-900">Account Access</h3>
                    <p class="mt-2 text-sm text-slate-700">Login and role support.</p>
                </article>

                <article class="rounded-2xl border border-emerald-200 bg-white p-6 sm:p-7">
                    <h3 class="text-lg sm:text-xl font-bold text-emerald-900">Timetables</h3>
                    <p class="mt-2 text-sm text-slate-700">Scheduling assistance.</p>
                </article>

                <article class="rounded-2xl border border-emerald-200 bg-white p-6 sm:p-7">
                    <h3 class="text-lg sm:text-xl font-bold text-emerald-900">Seating Plans</h3>
                    <p class="mt-2 text-sm text-slate-700">Exam seating help.</p>
                </article>

            </div>

        </div>

    </section>

</main>

<?php include 'includes/public_footer.php'; ?>