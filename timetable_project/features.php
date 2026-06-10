<?php
$pageTitle = 'Features | Academic Scheduling & Seating Suite';
include 'includes/public_header.php';
?>

<main class="bg-emerald-50/40">

    <!-- HEADER -->
    <section class="border-b border-emerald-200 bg-white">

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

            <p class="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-emerald-800">
                System Features
            </p>

            <h1 class="mt-2 text-2xl sm:text-3xl lg:text-4xl font-extrabold leading-tight text-emerald-950">
                Everything needed for timetable and seating workflows.
            </h1>

            <p class="mt-4 sm:mt-5 max-w-3xl text-base sm:text-lg leading-7 text-slate-700">
                The secured portal manages academic data, timetables, seating, and role-based access.
            </p>

        </div>
    </section>

    <!-- FEATURES GRID -->
    <section class="mx-auto max-w-7xl px-4 py-10 sm:py-14 sm:px-6 lg:px-8">

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">

            <?php
            $features = [
                ['Timetable Generation', 'Create schedules using academic constraints and rules.'],
                ['Seat Plan Generation', 'Assign students to rooms based on capacity.'],
                ['Master Data Management', 'Manage teachers, students, rooms, subjects, and departments.'],
                ['Role-Based Access', 'Separate access for admin, teacher, and student users.'],
                ['History Views', 'Track timetable and seating changes over time.'],
                ['Export Support', 'Printable, PDF, and Excel-ready outputs.'],
            ];

            foreach ($features as $feature):
            ?>

                <article class="rounded-2xl border border-emerald-200 bg-white p-5 sm:p-7 shadow-sm transition hover:shadow-md">

                    <div class="mb-4 h-9 w-9 sm:h-10 sm:w-10 rounded-md bg-emerald-900 ring-4 ring-emerald-100"></div>

                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-900">
                        <?= htmlspecialchars($feature[0]) ?>
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-slate-700">
                        <?= htmlspecialchars($feature[1]) ?>
                    </p>

                </article>

            <?php endforeach; ?>

        </div>

        <!-- DARK SECTION -->
        <div class="mt-10 sm:mt-14 rounded-2xl border border-emerald-200 bg-emerald-900 p-6 sm:p-8 text-white">

            <h2 class="text-xl sm:text-2xl font-extrabold">
                Administrative Feature Groups
            </h2>

            <div class="mt-6 sm:mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

                <div class="rounded-2xl bg-white/10 p-5 sm:p-6 ring-1 ring-white/15">
                    <h3 class="text-base sm:text-lg font-bold">Data Management</h3>
                    <p class="mt-2 text-sm text-emerald-50 leading-6">
                        Institution, departments, teachers, students, rooms, and classes.
                    </p>
                </div>

                <div class="rounded-2xl bg-white/10 p-5 sm:p-6 ring-1 ring-white/15">
                    <h3 class="text-base sm:text-lg font-bold">Planning Operations</h3>
                    <p class="mt-2 text-sm text-emerald-50 leading-6">
                        Scheduling, constraints, and seating plan generation.
                    </p>
                </div>

                <div class="rounded-2xl bg-white/10 p-5 sm:p-6 ring-1 ring-white/15">
                    <h3 class="text-base sm:text-lg font-bold">Output Access</h3>
                    <p class="mt-2 text-sm text-emerald-50 leading-6">
                        Export, print, and role-based timetable viewing.
                    </p>
                </div>

            </div>
        </div>

    </section>

</main>

<?php include 'includes/public_footer.php'; ?>