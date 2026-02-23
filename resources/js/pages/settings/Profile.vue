<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    server_slug?: string;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: props.server_slug ? `${props.server_slug}/settings/profile` : '/settings/profile',
    },
];

const page = usePage();
const user = page.props.auth.user as User;

// const form = useForm({
//     avatar: null as File | null,
// });

// const handleAvatarChange = (e: Event) => {
//     const fileInput = e.target as HTMLInputElement;
//     const file = fileInput.files?.[0];
//     if (file) {
//         form.avatar = file;
//         avatarPreview.value = URL.createObjectURL(file);
//     }
// };

// const submit = () => {
//     form.patch(route('profile.update'), {
//         preserveScroll: true,
//         forceFormData: true,
//     });
// };
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class=" flex flex-col space-y-6 md:max-w-3xl">
                <HeadingSmall title="Profile information" description="" />

                <form class="space-y-6" >
                    <!-- Display name -->
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" type="text" v-model="user.name" disabled />
                    </div>

                    <!-- Display email -->
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" type="email" v-model="user.email" disabled />
                    </div>

                    <!-- Display B-number -->
                    <div class="grid gap-2">
                        <Label for="bnumber">B-Number</Label>
                        <Input id="bnumber" type="text" v-model="user.unique_id" disabled />
                    </div>

                    
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

