    <!-- Sidebar -->
    <div class="hidden md:flex md:flex-shrink-0 shadow-lg z-10">
        <div class="flex flex-col w-64">
            <div class="flex flex-col h-0 flex-1 bg-slate-900 relative">
                <div class="flex-1 flex flex-col pt-6 pb-4 overflow-y-auto">
                    <div class="flex items-center flex-shrink-0 px-6 gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-md">
                            TS
                        </div>
                        <span class="text-white text-lg font-bold tracking-wide">Ticketing</span>
                    </div>
                    
                    <!-- user info -->
                    <div class="mt-8 px-6 pb-6 border-b border-slate-800">
                        <div class="text-slate-400 text-xs font-medium uppercase tracking-wider mb-1">Signed in as</div>
                        <div class="text-white font-semibold flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                            <span class="truncate"><?= e($currentUser['username'] ?? 'Unknown'); ?></span>
                        </div>
                        <div class="text-indigo-400 text-xs font-semibold mt-1.5 inline-block px-2 py-0.5 rounded-full bg-indigo-500/10 border border-indigo-500/20">
                            <?= e(str_replace('_', ' ', $currentUser['role'] ?? '')); ?>
                        </div>
                    </div>

                    <nav class="mt-6 flex-1 px-3 space-y-1">
                        <?php if ($currentUser['role'] === 'End_User'): ?>
                            <a href="end_user_dashboard.php" class="text-slate-300 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                                <svg class="text-slate-400 group-hover:text-white mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                My Tickets
                            </a>
                            <a href="create_ticket.php" class="text-slate-300 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                                <svg class="text-slate-400 group-hover:text-white mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Create Ticket
                            </a>
                        <?php elseif ($currentUser['role'] === 'IT_Agent'): ?>
                            <a href="agent_queue.php" class="text-slate-300 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                                <svg class="text-slate-400 group-hover:text-white mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                Support Queue
                            </a>
                        <?php elseif ($currentUser['role'] === 'IT_Head'): ?>
                            <a href="head_dashboard.php" class="text-slate-300 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                                <svg class="text-slate-400 group-hover:text-white mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                Overview & Reports
                            </a>
                            <a href="head_slas.php" class="text-slate-300 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                                <svg class="text-slate-400 group-hover:text-white mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                SLA Policies
                            </a>
                        <?php elseif ($currentUser['role'] === 'Admin'): ?>
                            <a href="admin_panel.php" class="text-slate-300 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                                <svg class="text-slate-400 group-hover:text-white mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                System Config
                            </a>
                            <a href="admin_users.php" class="text-slate-300 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                                <svg class="text-slate-400 group-hover:text-white mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                User Management
                            </a>
                            <a href="admin_slas.php" class="text-slate-300 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                                <svg class="text-slate-400 group-hover:text-white mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                SLA Policies
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
                
                <!-- bottom tools -->
                <div class="flex-shrink-0 flex flex-col gap-2 bg-slate-950 p-4" x-data="{ theme: localStorage.getItem('color-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') }">
                    <button @click="theme = theme === 'dark' ? 'light' : 'dark'; localStorage.setItem('color-theme', theme); document.documentElement.classList.toggle('dark')" class="w-full text-slate-400 hover:text-white transition-colors duration-200 flex items-center justify-center gap-2 py-2 rounded-lg hover:bg-slate-800">
                        <svg x-show="theme === 'dark'" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg x-show="theme !== 'dark'" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                        <span class="text-sm font-medium" x-text="theme === 'dark' ? 'Light Mode' : 'Dark Mode'"></span>
                    </button>
                    <a href="logout.php" class="w-full text-slate-400 hover:text-white transition-colors duration-200 flex items-center justify-center gap-2 py-2 rounded-lg hover:bg-slate-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="text-sm font-medium">Log out</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main content container -->
    <div class="flex flex-col w-0 flex-1 overflow-hidden bg-slate-50 dark:bg-dark-bg transition-colors duration-200">
        <main class="flex-1 relative z-0 overflow-y-auto focus:outline-none">
            <div class="py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
