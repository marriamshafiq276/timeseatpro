<!-- Public layout footer shared by visitor-facing pages. -->
<footer class="border-t border-emerald-900 bg-emerald-950 text-emerald-50">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.3fr_1fr_1fr] lg:px-8">

        <!-- About System -->
        <div>
            <h3 class="text-lg font-bold tracking-wide text-white">
                Academic Scheduling & Seating Suite
            </h3>

            <p class="mt-4 max-w-md text-sm leading-6 text-emerald-100">
                A centralized university administration platform for timetable
                scheduling, classroom seating management, and secure role-based
                academic access.
            </p>

            <!-- Professional Project Info -->
            <div class="mt-5 border-l-2 border-emerald-700 pl-4">
                <p class="text-sm text-emerald-200">
                    <span class="font-semibold text-white">
                        Project Supervisor:
                    </span>
                    Dr. Qamar Nawaz
                </p>

                <p class="mt-1 text-sm text-emerald-200">
                    <span class="font-semibold text-white">
                        Developed By:
                    </span>
                    Marriam Shafiq
                </p>
            </div>
        </div>

        <!-- Portal Links -->
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider text-emerald-200">
                Portal
            </h3>

            <div class="mt-4 grid gap-3 text-sm text-emerald-100">
                <a href="/timetable_project/index.php" class="transition hover:text-white">
                    Home
                </a>

                <a href="/timetable_project/about.php" class="transition hover:text-white">
                    About
                </a>

                <a href="/timetable_project/features.php" class="transition hover:text-white">
                    Features
                </a>

                <a href="/timetable_project/support.php" class="transition hover:text-white">
                    Support
                </a>

                <a href="/timetable_project/contact.php" class="transition hover:text-white">
                    Contact
                </a>
            </div>
        </div>

        <!-- Access Information -->
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider text-emerald-200">
                Access
            </h3>

            <p class="mt-4 text-sm leading-6 text-emerald-100">
                Authorized users can securely access the university portal through the login page.
            </p>

            <div class="mt-4 space-y-1 text-sm text-emerald-100">
                <p>Monday – Friday: 8:00 AM – 4:00 PM</p>
                <p>Phone: +92 41 9200161-70</p>
            </div>

            <a href="/timetable_project/login.php"
               class="mt-5 inline-flex rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-100">
                Login
            </a>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-emerald-800">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-emerald-300 sm:flex-row sm:px-6 lg:px-8">
            <p>
                © <?= date('Y') ?> Academic Scheduling & Seating Suite —
                University Administration System
            </p>

            <p>
                All Rights Reserved
            </p>
        </div>
    </div>
</footer>

</body>
</html>
