<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log In - Zunoi Caffe" />

    <div
        class="relative flex min-h-screen items-center justify-center bg-cover bg-center p-6 font-sans"
        style="
            background-image: url('https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1200&q=80');
        "
    >
        <!-- Dark Overlay -->
        <div
            class="absolute inset-0 z-0 bg-[#3B2314]/80 backdrop-blur-sm"
        ></div>

        <!-- Login Card -->
        <div
            class="relative z-10 w-full max-w-md rounded-3xl border border-white/20 bg-white/10 p-8 text-[#FAEDCD] shadow-2xl backdrop-blur-md"
        >
            <!-- Header/Logo -->
            <div class="mb-8 text-center">
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full border-2 border-white/30 bg-[#D4A373] text-2xl font-extrabold text-[#3B2314] shadow-lg"
                >
                    Z
                </div>
                <h1 class="text-3xl font-extrabold tracking-wide text-white">
                    ZUNOI CAFFE
                </h1>
                <p
                    class="mt-1 text-xs font-bold tracking-widest text-[#D4A373] uppercase"
                >
                    Admin & Barista Portal
                </p>
            </div>

            <div
                v-if="status"
                class="mb-4 rounded-xl border border-green-500/30 bg-green-500/20 p-3 text-center text-sm font-medium text-green-300"
            >
                {{ status }}
            </div>

            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5"
            >
                <div class="space-y-4">
                    <!-- Email field -->
                    <div class="space-y-2">
                        <Label
                            for="email"
                            class="text-sm font-semibold text-gray-200"
                            >Email Address</Label
                        >
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="nama@zunoi.id"
                            class="w-full rounded-xl border-white/20 bg-white/10 px-4 py-3 text-white placeholder-gray-400 focus:border-[#D4A373] focus:ring-[#D4A373]"
                        />
                        <InputError
                            :message="errors.email"
                            class="text-xs font-semibold text-red-400"
                        />
                    </div>

                    <!-- Password field -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label
                                for="password"
                                class="text-sm font-semibold text-gray-200"
                                >Password</Label
                            >
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-xs text-[#D4A373] hover:underline"
                                :tabindex="5"
                            >
                                Lupa Password?
                            </TextLink>
                        </div>
                        <PasswordInput
                            id="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="Password Anda"
                            class="w-full rounded-xl border-white/20 bg-white/10 px-4 py-3 text-white focus:border-[#D4A373] focus:ring-[#D4A373]"
                        />
                        <InputError
                            :message="errors.password"
                            class="text-xs font-semibold text-red-400"
                        />
                    </div>

                    <!-- Remember me -->
                    <div class="flex items-center justify-between pt-2">
                        <Label
                            for="remember"
                            class="flex cursor-pointer items-center space-x-3 text-sm text-gray-200"
                        >
                            <Checkbox
                                id="remember"
                                name="remember"
                                :tabindex="3"
                                class="rounded border-white/20 bg-white/10 text-[#3B2314] focus:ring-[#D4A373]"
                            />
                            <span>Ingat Saya</span>
                        </Label>
                    </div>

                    <!-- Login Button -->
                    <Button
                        type="submit"
                        class="text-md mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-[#D4A373] py-3.5 font-extrabold text-[#3B2314] shadow-lg transition-all duration-200 hover:scale-[1.02] hover:bg-[#c49363]"
                        :tabindex="4"
                        :disabled="processing"
                        data-test="login-button"
                    >
                        <Spinner v-if="processing" class="text-[#3B2314]" />
                        <span>Masuk ke Dashboard</span>
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
