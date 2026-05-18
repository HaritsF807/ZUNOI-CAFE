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

    <div class="min-h-screen flex items-center justify-center bg-cover bg-center relative p-6 font-sans" style="background-image: url('https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1200&q=80');">
        <!-- Dark Overlay -->
        <div class="absolute inset-0 bg-[#3B2314]/80 backdrop-blur-sm z-0"></div>

        <!-- Login Card -->
        <div class="relative z-10 w-full max-w-md bg-white/10 backdrop-blur-md border border-white/20 p-8 rounded-3xl shadow-2xl text-[#FAEDCD]">
            <!-- Header/Logo -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 rounded-full bg-[#D4A373] text-[#3B2314] flex items-center justify-center font-extrabold text-2xl shadow-lg mx-auto mb-4 border-2 border-white/30">
                    Z
                </div>
                <h1 class="text-3xl font-extrabold text-white tracking-wide">ZUNOI CAFFE</h1>
                <p class="text-xs text-[#D4A373] uppercase tracking-widest font-bold mt-1">Admin & Barista Portal</p>
            </div>

            <div
                v-if="status"
                class="mb-4 p-3 bg-green-500/20 border border-green-500/30 rounded-xl text-center text-sm font-medium text-green-300"
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
                        <Label for="email" class="text-sm font-semibold text-gray-200">Email Address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="nama@zunoi.id"
                            class="w-full bg-white/10 border-white/20 text-white rounded-xl py-3 px-4 focus:ring-[#D4A373] focus:border-[#D4A373] placeholder-gray-400"
                        />
                        <InputError :message="errors.email" class="text-red-400 text-xs font-semibold" />
                    </div>

                    <!-- Password field -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-sm font-semibold text-gray-200">Password</Label>
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
                            class="w-full bg-white/10 border-white/20 text-white rounded-xl py-3 px-4 focus:ring-[#D4A373] focus:border-[#D4A373]"
                        />
                        <InputError :message="errors.password" class="text-red-400 text-xs font-semibold" />
                    </div>

                    <!-- Remember me -->
                    <div class="flex items-center justify-between pt-2">
                        <Label for="remember" class="flex items-center space-x-3 text-sm text-gray-200 cursor-pointer">
                            <Checkbox id="remember" name="remember" :tabindex="3" class="rounded border-white/20 bg-white/10 text-[#3B2314] focus:ring-[#D4A373]" />
                            <span>Ingat Saya</span>
                        </Label>
                    </div>

                    <!-- Login Button -->
                    <Button
                        type="submit"
                        class="w-full bg-[#D4A373] text-[#3B2314] font-extrabold py-3.5 rounded-xl text-md shadow-lg hover:bg-[#c49363] hover:scale-[1.02] transition-all duration-200 mt-4 flex items-center justify-center gap-2"
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
