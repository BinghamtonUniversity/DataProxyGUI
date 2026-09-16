<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <Head title="Welcome to BITS Proxy">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
        <!-- Header -->
        <header class="relative z-10">
            <nav class="flex items-center justify-between p-6 lg:px-8">
                <div class="flex items-center">
                    <AppLogoIcon variant="mark" class="size-12 object-contain" />
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="relative">
            <!-- Hero Section -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
                <div class="text-center">
                    <!-- Logo -->
                    <div class="flex justify-center mb-8">
                        <div class="rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-950">
                            <AppLogoIcon variant="full" class="h-28 w-auto max-w-[280px] object-contain" />
                        </div>
                    </div>

                    <!-- Main Heading -->
                    <h1 class="text-5xl lg:text-7xl font-bold text-gray-900 dark:text-white mb-3">
                        BITS Proxy
                    </h1>

                    <p class="text-lg lg:text-xl font-semibold tracking-wide text-gray-700 dark:text-gray-300 mb-6">
                        Binghamton ITS Proxy
                    </p>
                    
                    <!-- Subtitle -->
                    <p class="text-xl lg:text-2xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto">
                        Your gateway to seamless data integration and API management
                    </p>

                    <!-- Description -->
                    <p class="text-lg text-gray-500 dark:text-gray-400 mb-12 max-w-2xl mx-auto">
                        Streamline your data workflows with our powerful proxy solution. 
                        Connect, transform, and manage your APIs with enterprise-grade security and performance.
                    </p>

                    <!-- CTA Button -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <a
                            :href="route('oidc.redirect')"
                            class="inline-flex items-center px-8 py-4 bg-blue-600 text-white text-lg font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-xl hover:shadow-2xl transform hover:-translate-y-1"
                        >
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                            Get Started
                        </a>
                        
                        <button class="inline-flex items-center px-8 py-4 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-lg font-semibold rounded-xl hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Learn More
                        </button>
                    </div>
                </div>
            </div>

           
            <!-- Footer -->
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
               
            </footer>
        </main>
    </div>
</template>
