<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'; import { computed, ref } from 'vue';
const page=usePage(), open=ref(false), dark=ref(document.documentElement.classList.contains('dark'));
const nav=[['Dashboard','/'],['Live','/live'],['Team Lab','/team-lab'],['Standings','/standings'],['Managers','/managers'],['Insights','/stats'],['Hall','/hall-of-fame']];
const toggle=()=>{dark.value=!dark.value;document.documentElement.classList.toggle('dark',dark.value);localStorage.theme=dark.value?'dark':'light'};
const initials=computed(()=>page.props.auth.user?.name?.split(' ').map(v=>v[0]).join('').slice(0,2).toUpperCase());
</script>
<template><div class="min-h-screen bg-[#f3f5ef] text-[#10271d] dark:bg-[#07110d] dark:text-[#e7eee9]">
<header class="sticky top-0 z-40 border-b border-black/5 bg-[#f3f5ef]/90 backdrop-blur-xl dark:border-white/10 dark:bg-[#07110d]/90"><div class="mx-auto flex h-18 max-w-7xl items-center px-5 lg:px-8">
<Link href="/" class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-[#123d2c] text-lg font-black text-[#f4d35e]">M</span><span><b class="block text-lg tracking-tight">MANDEM</b><small class="block -mt-1 text-[10px] font-bold tracking-[.2em] text-[#7b8b83]">FANTASY CLUB</small></span></Link>
<nav class="mx-auto hidden items-center gap-1 md:flex"><Link v-for="n in nav" :key="n[0]" :href="n[1]" class="rounded-full px-3 py-2 text-sm font-semibold" :class="page.url.startsWith(n[1])&&n[1]!=='/'||page.url==='/'&&n[1]==='/'?'bg-[#163f2e] text-white dark:bg-[#f4d35e] dark:text-[#17241d]':'text-[#65756d] hover:bg-black/5 dark:text-[#9cada5] dark:hover:bg-white/5'">{{n[0]}}</Link></nav>
<div class="ml-auto flex items-center gap-2"><button @click="toggle" class="grid size-10 place-items-center rounded-full border border-black/8 dark:border-white/10">{{dark?'☀':'☾'}}</button><div class="hidden size-10 place-items-center rounded-full bg-[#dfe9df] text-xs font-bold dark:bg-[#1c352a] sm:grid">{{initials}}</div><button @click="router.post('/logout')" class="hidden text-sm text-[#708078] hover:text-red-500 lg:block">Sign out</button><button @click="open=!open" class="ml-1 md:hidden">☰</button></div></div>
<nav v-if="open" class="grid gap-1 border-t border-black/5 p-4 md:hidden"><Link v-for="n in nav" :href="n[1]" class="rounded-lg px-3 py-2 font-semibold">{{n[0]}}</Link></nav></header>
<main class="mx-auto max-w-7xl px-5 py-8 lg:px-8 lg:py-11"><slot/></main>
<footer class="mx-auto max-w-7xl border-t border-black/5 px-5 py-8 text-xs text-[#7c8b83] dark:border-white/10 lg:px-8"><div class="flex flex-wrap justify-between gap-3"><span>© {{new Date().getFullYear()}} Mandem Fantasy Club</span><span>Unofficial fan project · Not affiliated with the Premier League</span></div></footer></div></template>
