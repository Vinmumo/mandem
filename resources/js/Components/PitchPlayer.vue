<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({player:Object, planning:Boolean});
defineEmits(['replace']);
</script>
<template><div class="group relative flex w-22 flex-col items-center text-center sm:w-27">
 <div class="relative grid size-13 place-items-center rounded-2xl border-2 border-white/70 bg-[#f5d45f] font-black text-[#153426] shadow-lg sm:size-15">
  {{player.name.slice(0,2).toUpperCase()}}
  <span v-if="player.is_captain" class="absolute -right-2 -top-2 grid size-6 place-items-center rounded-full bg-[#10271d] text-[10px] text-white">C</span>
  <span v-else-if="player.is_vice_captain" class="absolute -right-2 -top-2 grid size-6 place-items-center rounded-full bg-white text-[9px] text-[#10271d]">V</span>
 </div>
 <Link :href="`/players/${player.id}`" class="mt-1 max-w-full truncate rounded-md bg-[#0c281c]/90 px-2 py-1 text-[11px] font-bold text-white hover:bg-[#f4d35e] hover:text-[#10271d]">{{player.name}}</Link>
 <span class="mt-1 text-[10px] font-bold text-white/80">£{{(player.price/10).toFixed(1)}} · {{player.points}} pts</span>
 <button v-if="planning" @click="$emit('replace',player)" class="absolute -bottom-7 rounded-full bg-white px-2 py-1 text-[9px] font-black uppercase tracking-wide text-[#17432f] opacity-100 shadow sm:opacity-0 sm:group-hover:opacity-100">Replace</button>
</div></template>
