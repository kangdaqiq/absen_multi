@extends('layouts.app')

@php
    $title = 'RFID Simulator';
@endphp

@section('content')
<div class="container mx-auto px-4 py-6" x-data="rfidSimulator()">
    <!-- Header Banner -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 rounded-2xl shadow-xl border border-indigo-900/50">
        <div>
            <h1 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                <i class="fas fa-microchip text-indigo-400"></i> RFID Scanner Simulator
            </h1>
            <p class="text-slate-300 text-sm mt-1">
                Developer environment to simulate physical RFID scans, verify attendance rules, and debug enrollment.
            </p>
        </div>
        <div class="flex items-center gap-2 text-xs bg-indigo-900/40 border border-indigo-700/30 rounded-lg px-3 py-2 self-start md:self-center text-indigo-300">
            <i class="fas fa-lock text-indigo-400"></i> Dev Mode Portal (Hidden)
        </div>
    </div>

    <!-- Alert / Tip -->
    <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-xl dark:bg-blue-950/20 dark:border-blue-700">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500 dark:text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <strong class="font-semibold">How to enroll a new card:</strong> Go to the Siswa/Guru management list, click "Enroll" on a person to set their status to "requested". Then type or select a new UID card here and press "SCAN CARD".
                </p>
            </div>
        </div>
    </div>

    @if(!$selectedDevice)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 p-6 rounded-xl dark:bg-amber-950/20 dark:border-amber-800 dark:text-amber-300 text-center">
            <i class="fas fa-exclamation-triangle text-3xl mb-3 text-amber-500"></i>
            <h3 class="text-lg font-bold">No Active RFID Device Found</h3>
            <p class="mt-1">You must register and activate at least one RFID/Fingerprint device in the Device Management system to use this simulator.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- LEFT PANEL: Simulator & Inputs -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <!-- VIRTUAL RFID HARDWARE -->
                <div class="bg-slate-900 border-4 border-slate-700 rounded-3xl shadow-2xl p-6 text-white relative overflow-hidden flex flex-col items-center justify-between min-h-[380px]">
                    <!-- Metallic accent lines -->
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-slate-500 to-transparent opacity-30"></div>
                    <div class="absolute -right-16 -top-16 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl"></div>

                    <!-- Device Branding -->
                    <div class="w-full flex justify-between items-center border-b border-slate-800 pb-3 mb-4">
                        <span class="text-[10px] font-mono tracking-widest text-slate-400">JT-RFID v2.1</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-mono text-slate-400">LED:</span>
                            <!-- LED Indicators -->
                            <div class="flex gap-1.5">
                                <div class="w-3.5 h-3.5 rounded-full border border-slate-800 transition-all duration-300"
                                     :class="ledState === 'success' ? 'bg-emerald-500 shadow-[0_0_12px_#10b981]' : 'bg-emerald-950 opacity-40'"></div>
                                <div class="w-3.5 h-3.5 rounded-full border border-slate-800 transition-all duration-300"
                                     :class="ledState === 'warning' ? 'bg-amber-500 shadow-[0_0_12px_#f59e0b]' : 'bg-amber-950 opacity-40'"></div>
                                <div class="w-3.5 h-3.5 rounded-full border border-slate-800 transition-all duration-300"
                                     :class="ledState === 'error' ? 'bg-rose-500 shadow-[0_0_12px_#f43f5e]' : 'bg-rose-950 opacity-40'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- LCD Screen -->
                    <div class="font-mono bg-[#0c160e] border-2 border-[#1a2f1e] text-[#39ff14] p-4 rounded-xl shadow-inner w-full min-h-[120px] text-xs flex flex-col justify-between overflow-y-auto mb-5 leading-relaxed selection:bg-emerald-800">
                        <div class="space-y-1">
                            <div class="flex justify-between border-b border-[#1a2f1e] pb-1 text-[10px] text-emerald-600 mb-1 font-bold">
                                <span>DEVICE: {{ $selectedDevice->name }}</span>
                                <span class="animate-pulse" x-text="screenTime">00:00:00</span>
                            </div>
                            <template x-for="line in lcdScreenLines">
                                <div class="break-words" x-text="line"></div>
                            </template>
                        </div>
                        <div class="text-[9px] text-emerald-700 text-right mt-2 border-t border-[#122216] pt-1">
                            Status: <span x-text="deviceStatus">IDLE</span>
                        </div>
                    </div>

                    <!-- Scan Contactless Target Area -->
                    <div class="relative w-full py-5 bg-slate-950 border border-slate-800 rounded-2xl flex flex-col items-center justify-center group cursor-pointer hover:border-slate-700 transition-all"
                         @click="triggerScan()">
                        <div class="absolute inset-0 bg-indigo-500/5 opacity-0 group-hover:opacity-100 transition-all rounded-2xl"></div>
                        <div class="w-14 h-14 rounded-full border border-slate-800 flex items-center justify-center mb-2 bg-slate-900 group-hover:scale-105 transition-transform"
                             :class="isScanning ? 'border-indigo-500 shadow-[0_0_15px_rgba(99,102,241,0.4)]' : ''">
                            <i class="fas fa-rss text-2xl" :class="isScanning ? 'text-indigo-400 animate-pulse' : 'text-slate-500 group-hover:text-slate-300'"></i>
                        </div>
                        <span class="text-xs font-semibold text-slate-400 group-hover:text-slate-200">TAP RFID CARD HERE</span>
                        <span class="text-[10px] text-slate-600 mt-0.5">Click to simulate scanner hardware touch</span>

                        <!-- Scanning pulse waves -->
                        <div x-show="isScanning" class="absolute inset-0 border border-indigo-500/30 rounded-2xl animate-ping opacity-75 pointer-events-none"></div>
                    </div>
                </div>

                <!-- SIMULATOR CONTROLS -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 rounded-2xl shadow-sm">
                    <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                        <i class="fas fa-sliders-h text-slate-500"></i> Simulator Settings
                    </h3>

                    <form @submit.prevent="triggerScan()" class="space-y-4">
                        <!-- Select Device -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Selected Scanner Hardware</label>
                            <select class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                    x-model="deviceId" @change="changeDevice($event.target.value)">
                                @foreach($devices as $dev)
                                    <option value="{{ $dev->id }}" {{ $selectedDevice->id == $dev->id ? 'selected' : '' }}>
                                        {{ $dev->name }} (School: {{ $dev->school->nama ?? 'Global' }} / ID: {{ $dev->id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Readonly API Key -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">API Key (Device Key)</label>
                            <div class="relative">
                                <input type="text" readonly class="w-full text-xs font-mono bg-gray-50 border border-gray-200 text-gray-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-400 rounded-lg py-2 pl-3 pr-10"
                                       value="{{ $selectedDevice->api_key }}">
                                <i class="fas fa-key text-gray-400 absolute right-3 top-2.5"></i>
                            </div>
                        </div>

                        <!-- UID RFID input -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">RFID Card UID (Hex 8-20 chars)</label>
                            <input type="text" x-model="rfidUid" placeholder="E.g. A1B2C3D4" required
                                   class="w-full text-sm font-mono border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500 uppercase tracking-widest pl-3 py-2">
                        </div>

                        <!-- Time Travel (scanned_at) -->
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400">Simulation Date/Time (Time Travel)</label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" x-model="useCustomTime" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 text-[10px]">
                                    <span class="text-[10px] text-gray-500">Custom Time</span>
                                </label>
                            </div>
                            <div x-show="useCustomTime" x-transition>
                                <input type="datetime-local" x-model="customTime"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-2 px-3">
                                <p class="text-[10px] text-indigo-500 mt-1"><i class="fas fa-magic"></i> Great for testing specific days (e.g. Siswa Khusus schedules) or late-marking rules.</p>
                            </div>
                            <div x-show="!useCustomTime" class="text-xs text-gray-400 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-2.5 rounded-lg">
                                <i class="fas fa-clock text-slate-400 mr-1.5"></i> Running in real-time mode (uses current server timestamp)
                            </div>
                        </div>

                        <!-- Trigger Button -->
                        <button type="submit" :disabled="isScanning"
                                class="w-full py-3.5 text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 rounded-xl font-bold shadow-lg shadow-indigo-600/20 transition-all cursor-pointer transform active:scale-95 flex items-center justify-center gap-2">
                            <i class="fas fa-broadcast-tower" :class="isScanning ? 'animate-spin' : ''"></i>
                            <span x-text="isScanning ? 'Simulating...' : 'SCAN RFID CARD'">SCAN RFID CARD</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT PANEL: Candidates Selector -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full max-h-[730px]">
                    <!-- Tabs Headers -->
                    <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-2">
                        <button @click="activeTab = 'siswa'" :class="activeTab === 'siswa' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                                class="flex-1 py-2.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-user-graduate"></i> Students ({{ $siswa->count() }})
                        </button>
                        <button @click="activeTab = 'guru'" :class="activeTab === 'guru' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                                class="flex-1 py-2.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-chalkboard-teacher"></i> Teachers ({{ $guru->count() }})
                        </button>
                        <button @click="activeTab = 'gate'" :class="activeTab === 'gate' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                                class="flex-1 py-2.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-door-open"></i> Gate Cards ({{ $gateCards->count() }})
                        </button>
                        <button @click="activeTab = 'custom'" :class="activeTab === 'custom' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                                class="flex-1 py-2.5 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-plus-circle"></i> Custom / Temp
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <div class="relative">
                            <input type="text" x-model="searchQuery" placeholder="Search by name, NIS, NIP or RFID UID..."
                                   class="w-full text-sm pl-10 pr-4 py-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                            <i class="fas fa-search text-gray-400 absolute left-3.5 top-3 text-sm"></i>
                            <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3.5 top-2.5 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Candidates List Container -->
                    <div class="flex-1 overflow-y-auto p-4 min-h-[350px]">
                        <!-- SISWA TAB -->
                        <div x-show="activeTab === 'siswa'" class="space-y-2">
                            <template x-for="item in filteredSiswa" :key="item.id">
                                <div class="flex items-center justify-between p-3.5 bg-gray-50 hover:bg-indigo-50/40 dark:bg-gray-900/50 dark:hover:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl transition-all">
                                    <div class="flex flex-col gap-1 min-w-0 pr-4">
                                        <span class="font-bold text-sm text-gray-800 dark:text-gray-200 truncate" x-text="item.nama"></span>
                                        <div class="flex flex-wrap items-center gap-1.5 text-xs text-gray-500">
                                            <span class="bg-gray-200 dark:bg-gray-800 px-1.5 py-0.5 rounded text-[10px]" x-text="'Kelas: ' + (item.kelas ? item.kelas.nama_kelas : '-')"></span>
                                            <span x-text="'NIS: ' + item.nis"></span>
                                            <span class="font-mono text-[10px]" :class="item.uid_rfid ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-500'" x-text="item.uid_rfid ? 'UID: ' + item.uid_rfid : 'No RFID Tag'"></span>
                                        </div>
                                        <!-- Special features badges -->
                                        <div class="flex gap-1.5 mt-1">
                                            <template x-if="item.is_khusus">
                                                <span class="bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 px-2 py-0.5 rounded-full text-[9px] font-bold">PKL (Auto Present)</span>
                                            </template>
                                            <template x-if="item.is_siswa_khusus">
                                                <span class="bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300 px-2 py-0.5 rounded-full text-[9px] font-bold"
                                                      x-text="'Siswa Khusus (' + formatEntryDays(item.hari_masuk) + ')'"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <button @click="selectCard(item.uid_rfid || generateRandomUID(), item.nama)"
                                            class="flex-shrink-0 px-3.5 py-1.5 text-xs font-bold text-indigo-600 hover:text-white border border-indigo-200 hover:bg-indigo-600 dark:border-indigo-800 dark:hover:bg-indigo-600 rounded-lg transition-all cursor-pointer">
                                        <span x-text="item.uid_rfid ? 'Select Tag' : 'Simulate Enroll'"></span>
                                    </button>
                                </div>
                            </template>
                            <div x-show="filteredSiswa.length === 0" class="text-center py-8 text-gray-400">
                                <i class="fas fa-user-slash text-2xl mb-2"></i>
                                <p class="text-sm">No students match your search.</p>
                            </div>
                        </div>

                        <!-- GURU TAB -->
                        <div x-show="activeTab === 'guru'" class="space-y-2">
                            <template x-for="item in filteredGuru" :key="item.id">
                                <div class="flex items-center justify-between p-3.5 bg-gray-50 hover:bg-indigo-50/40 dark:bg-gray-900/50 dark:hover:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl transition-all">
                                    <div class="flex flex-col gap-1 min-w-0 pr-4">
                                        <span class="font-bold text-sm text-gray-800 dark:text-gray-200 truncate" x-text="item.nama"></span>
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <span x-text="'NIP: ' + (item.nip || '-')"></span>
                                            <span class="font-mono text-[10px]" :class="item.uid_rfid ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-500'" x-text="item.uid_rfid ? 'UID: ' + item.uid_rfid : 'No RFID Tag'"></span>
                                        </div>
                                    </div>
                                    <button @click="selectCard(item.uid_rfid || generateRandomUID(), item.nama)"
                                            class="flex-shrink-0 px-3.5 py-1.5 text-xs font-bold text-indigo-600 hover:text-white border border-indigo-200 hover:bg-indigo-600 dark:border-indigo-800 dark:hover:bg-indigo-600 rounded-lg transition-all cursor-pointer">
                                        <span x-text="item.uid_rfid ? 'Select Tag' : 'Simulate Enroll'"></span>
                                    </button>
                                </div>
                            </template>
                            <div x-show="filteredGuru.length === 0" class="text-center py-8 text-gray-400">
                                <i class="fas fa-user-slash text-2xl mb-2"></i>
                                <p class="text-sm">No teachers match your search.</p>
                            </div>
                        </div>

                        <!-- GATE CARD TAB -->
                        <div x-show="activeTab === 'gate'" class="space-y-2">
                            <template x-for="item in filteredGate" :key="item.id">
                                <div class="flex items-center justify-between p-3.5 bg-gray-50 hover:bg-indigo-50/40 dark:bg-gray-900/50 dark:hover:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl transition-all">
                                    <div class="flex flex-col gap-1 min-w-0 pr-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-sm text-gray-800 dark:text-gray-200 truncate" x-text="item.name"></span>
                                            <span class="bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 px-2 py-0.5 rounded text-[8px] font-bold">Gate Permission</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <span x-text="item.guru ? 'Owner: ' + item.guru.nama : 'Owner: Generic Gate'"></span>
                                            <span class="font-mono text-[10px] text-indigo-600 dark:text-indigo-400" x-text="'UID: ' + item.uid_rfid"></span>
                                        </div>
                                    </div>
                                    <button @click="selectCard(item.uid_rfid, item.name)"
                                            class="flex-shrink-0 px-3.5 py-1.5 text-xs font-bold text-indigo-600 hover:text-white border border-indigo-200 hover:bg-indigo-600 dark:border-indigo-800 dark:hover:bg-indigo-600 rounded-lg transition-all cursor-pointer">
                                        Select Tag
                                    </button>
                                </div>
                            </template>
                            <div x-show="filteredGate.length === 0" class="text-center py-8 text-gray-400">
                                <i class="fas fa-door-closed text-2xl mb-2"></i>
                                <p class="text-sm">No gate cards match your search.</p>
                            </div>
                        </div>

                        <!-- CUSTOM CARD TAB -->
                        <div x-show="activeTab === 'custom'" class="space-y-4">
                            <div class="bg-slate-50 dark:bg-gray-900 border border-slate-100 dark:border-slate-800 p-4 rounded-xl">
                                <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-2">Simulate Brand New/Unknown RFID Tag</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Click below to generate random 8-character hex tags. Good for testing unregistered cards or doing enrollment runs.</p>

                                <div class="grid grid-cols-2 gap-3">
                                    <button @click="generateAndSelect()"
                                            class="py-3 px-4 text-xs font-semibold text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-white bg-white hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm transition-all cursor-pointer flex flex-col items-center justify-center gap-1.5">
                                        <i class="fas fa-random text-lg text-slate-500"></i>
                                        Generate Random UID
                                    </button>
                                    <button @click="selectCard('01020304', 'Static Card 1')"
                                            class="py-3 px-4 text-xs font-semibold text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-white bg-white hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm transition-all cursor-pointer flex flex-col items-center justify-center gap-1.5">
                                        <span class="font-mono text-sm text-indigo-500">01020304</span>
                                        Select Static Card A
                                    </button>
                                    <button @click="selectCard('ABCD5566', 'Static Card 2')"
                                            class="py-3 px-4 text-xs font-semibold text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-white bg-white hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm transition-all cursor-pointer flex flex-col items-center justify-center gap-1.5">
                                        <span class="font-mono text-sm text-indigo-500">ABCD5566</span>
                                        Select Static Card B
                                    </button>
                                    <button @click="selectCard('FFFFFFFF', 'Null Card')"
                                            class="py-3 px-4 text-xs font-semibold text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-white bg-white hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm transition-all cursor-pointer flex flex-col items-center justify-center gap-1.5">
                                        <span class="font-mono text-sm text-indigo-500">FFFFFFFF</span>
                                        Select Null Card
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOTTOM PANEL: RECENT SCAN LOGS -->
        <div class="mt-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden" id="logs-container">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                <div>
                    <h3 class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                        <i class="fas fa-list-alt text-slate-500"></i> Recent Scan Database Logs
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Logs generated on the <code>api_logs</code> table for device API Key: <code>{{ substr($selectedDevice->api_key, 0, 8) }}...</code></p>
                </div>
                <button @click="refreshLogs()" class="px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 bg-white hover:bg-slate-100 dark:bg-gray-700 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm transition-all cursor-pointer flex items-center gap-1.5">
                    <i class="fas fa-sync" :class="isRefreshingLogs ? 'animate-spin' : ''"></i> Refresh logs
                </button>
            </div>

            <div class="overflow-x-auto" id="logs-table-container">
                <table class="w-full text-left text-sm border-collapse" id="logs-table">
                    <thead>
                        <tr class="bg-gray-100/50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 text-xs font-bold uppercase">
                            <th class="py-3 px-5">ID</th>
                            <th class="py-3 px-5">Action</th>
                            <th class="py-3 px-5">UID RFID</th>
                            <th class="py-3 px-5">Success</th>
                            <th class="py-3 px-5">Message</th>
                            <th class="py-3 px-5">Client Info</th>
                            <th class="py-3 px-5">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                        @forelse($recentLogs as $log)
                            <tr class="hover:bg-gray-50/40 dark:hover:bg-gray-900/20 transition-colors">
                                <td class="py-3.5 px-5 font-mono text-xs">{{ $log->id }}</td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        {{ in_array($log->action, ['checkin_success', 'checkout_success', 'gate_access', 'enroll_success']) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : '' }}
                                        {{ in_array($log->action, ['gate_closed']) ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300' : '' }}
                                        {{ in_array($log->action, ['unknown_card', 'rate_limit', 'validation_error', 'auth_failed']) ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300' : '' }}
                                    ">
                                        {{ str_replace('_', ' ', $log->action) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 font-mono text-xs font-semibold">{{ $log->uid ?: '-' }}</td>
                                <td class="py-3.5 px-5">
                                    @if($log->success)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold"><i class="fas fa-check-circle"></i> YES</span>
                                    @else
                                        <span class="text-rose-600 dark:text-rose-400 font-bold"><i class="fas fa-times-circle"></i> NO</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 text-xs">{{ $log->message }}</td>
                                <td class="py-3.5 px-5 text-[10px] text-gray-500 max-w-[200px] truncate" title="IP: {{ $log->ip_address }} | UA: {{ $log->user_agent }}">
                                    IP: {{ $log->ip_address }}<br>
                                    UA: {{ substr($log->user_agent, 0, 25) }}...
                                </td>
                                <td class="py-3.5 px-5 text-xs text-gray-500 font-mono">
                                    {{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i:s') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-400 dark:text-gray-500">
                                    <i class="fas fa-receipt text-3xl mb-2"></i>
                                    <p class="text-sm">No recent logs found for this device.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Web Audio API Synthesizer Sound Player
    const SoundPlayer = {
        ctx: null,
        init() {
            if (!this.ctx) {
                this.ctx = new (window.AudioContext || window.webkitAudioContext)();
            }
        },
        playSuccess() {
            this.init();
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(800, this.ctx.currentTime); // 800 Hz
            gain.gain.setValueAtTime(0.08, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.005, this.ctx.currentTime + 0.15); // fade out
            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start();
            osc.stop(this.ctx.currentTime + 0.15);
        },
        playWarning() {
            this.init();
            // Play two quick beeps
            this.beep(550, 0.07, 0);
            this.beep(550, 0.07, 0.12);
        },
        playError() {
            this.init();
            // Low buzzer sound
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(140, this.ctx.currentTime); // 140 Hz
            gain.gain.setValueAtTime(0.12, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.005, this.ctx.currentTime + 0.35);
            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start();
            osc.stop(this.ctx.currentTime + 0.35);
        },
        beep(freq, duration, delay) {
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, this.ctx.currentTime + delay);
            gain.gain.setValueAtTime(0.08, this.ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.005, this.ctx.currentTime + delay + duration);
            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start(this.ctx.currentTime + delay);
            osc.stop(this.ctx.currentTime + delay + duration);
        }
    };

    function rfidSimulator() {
        return {
            deviceId: '{{ $selectedDevice->id ?? "" }}',
            apiKey: '{{ $selectedDevice->api_key ?? "" }}',
            rfidUid: '',
            useCustomTime: false,
            customTime: '',
            activeTab: 'siswa',
            searchQuery: '',
            isScanning: false,
            isRefreshingLogs: false,

            // LCD State
            lcdScreenLines: [
                'SYSTEM BOOT: OK',
                'CONNECTING TO SERVER...',
                'STANDBY - READY FOR CARD'
            ],
            deviceStatus: 'STANDBY',
            ledState: 'idle', // success, warning, error, idle
            screenTime: '00:00:00',

            // Candidates Raw Data
            siswaData: @json($siswa),
            guruData: @json($guru),
            gateData: @json($gateCards),

            init() {
                // Set clock time
                setInterval(() => {
                    if (this.useCustomTime && this.customTime) {
                        const date = new Date(this.customTime);
                        this.screenTime = date.toTimeString().split(' ')[0];
                    } else {
                        const now = new Date();
                        this.screenTime = now.toTimeString().split(' ')[0];
                    }
                }, 1000);

                // Set initial default custom time (current local time formatted for datetime-local input)
                const now = new Date();
                const tzOffset = now.getTimezoneOffset() * 60000; // offset in milliseconds
                const localISOTime = (new Date(now - tzOffset)).toISOString().slice(0, 16);
                this.customTime = localISOTime;
            },

            // Filter Helpers
            get filteredSiswa() {
                if (!this.searchQuery) return this.siswaData;
                const q = this.searchQuery.toLowerCase();
                return this.siswaData.filter(s =>
                    s.nama.toLowerCase().includes(q) ||
                    s.nis.toLowerCase().includes(q) ||
                    (s.uid_rfid && s.uid_rfid.toLowerCase().includes(q))
                );
            },

            get filteredGuru() {
                if (!this.searchQuery) return this.guruData;
                const q = this.searchQuery.toLowerCase();
                return this.guruData.filter(g =>
                    g.nama.toLowerCase().includes(q) ||
                    (g.nip && g.nip.toLowerCase().includes(q)) ||
                    (g.uid_rfid && g.uid_rfid.toLowerCase().includes(q))
                );
            },

            get filteredGate() {
                if (!this.searchQuery) return this.gateData;
                const q = this.searchQuery.toLowerCase();
                return this.gateData.filter(gc =>
                    gc.name.toLowerCase().includes(q) ||
                    (gc.uid_rfid && gc.uid_rfid.toLowerCase().includes(q)) ||
                    (gc.guru && gc.guru.nama.toLowerCase().includes(q))
                );
            },

            formatEntryDays(days) {
                if (!days) return 'No days';
                const dayNames = {1:'Mon', 2:'Tue', 3:'Wed', 4:'Thu', 5:'Fri', 6:'Sat', 7:'Sun'};
                try {
                    let parsed = Array.isArray(days) ? days : JSON.parse(days);
                    if (!Array.isArray(parsed)) return 'No days';
                    return parsed.map(d => dayNames[d] || d).join(', ');
                } catch(e) {
                    return 'No days';
                }
            },

            changeDevice(id) {
                window.location.href = `{{ route('simulator.rfid') }}?device_id=${id}`;
            },

            selectCard(uid, name) {
                this.rfidUid = uid.toUpperCase();
                // Add message to screen
                this.lcdScreenLines = [
                    `CARD LOADED: ${uid.toUpperCase()}`,
                    `Candidate: ${name}`,
                    'READY TO TRANSMIT SCAN...'
                ];
                this.deviceStatus = 'CARD READY';
                this.ledState = 'idle';
            },

            generateRandomUID() {
                const chars = '0123456789ABCDEF';
                let res = '';
                for (let i = 0; i < 8; i++) {
                    res += chars[Math.floor(Math.random() * 16)];
                }
                return res;
            },

            generateAndSelect() {
                const uid = this.generateRandomUID();
                this.selectCard(uid, 'New Generated Tag');
            },

            generateAndSelectStatic(uid, name) {
                this.selectCard(uid, name);
            },

            triggerScan() {
                if (this.isScanning) return;
                if (!this.rfidUid) {
                    alert('Please enter or select a Card UID first.');
                    return;
                }

                // Format UID
                this.rfidUid = this.rfidUid.trim().toUpperCase();

                this.isScanning = true;
                this.deviceStatus = 'TRANSMITTING...';
                this.lcdScreenLines = [
                    `API POST -> /api/rfid`,
                    `UID: ${this.rfidUid}`,
                    this.useCustomTime ? `SIMULATED TIME: ${this.customTime.replace('T', ' ')}` : 'TIME: REAL-TIME (NOW)'
                ];

                // Prepare request parameters
                const params = {
                    api_key: this.apiKey,
                    uid: this.rfidUid
                };

                if (this.useCustomTime && this.customTime) {
                    // Send custom time formatted as Y-m-d H:i:s
                    const date = new Date(this.customTime);
                    const formattedDate = date.getFullYear() + '-' +
                        String(date.getMonth() + 1).padStart(2, '0') + '-' +
                        String(date.getDate()).padStart(2, '0') + ' ' +
                        String(date.getHours()).padStart(2, '0') + ':' +
                        String(date.getMinutes()).padStart(2, '0') + ':00';
                    params.scanned_at = formattedDate;
                }

                // POST TO API
                fetch('/api/rfid', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(params)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP Error ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Process response sound
                    const sound = data.sound || 'error';
                    if (sound === 'ok') {
                        SoundPlayer.playSuccess();
                        this.ledState = 'success';
                        this.deviceStatus = 'ACCESS GRANTED';
                    } else if (sound === 'warning') {
                        SoundPlayer.playWarning();
                        this.ledState = 'warning';
                        this.deviceStatus = 'WARNING';
                    } else {
                        SoundPlayer.playError();
                        this.ledState = 'error';
                        this.deviceStatus = 'ACCESS DENIED';
                    }

                    // Update screen message
                    this.lcdScreenLines = [
                        `RESP: ${data.ok ? 'SUCCESS' : 'FAILED'} (status: ${data.status})`,
                        `MSG: ${data.message || 'No description'}`,
                        data.nama ? `NAME: ${data.nama}` : `UID: ${this.rfidUid}`
                    ];

                    // Refresh logs dynamically
                    this.refreshLogs();
                })
                .catch(error => {
                    SoundPlayer.playError();
                    this.ledState = 'error';
                    this.deviceStatus = 'CONNECTION ERROR';
                    this.lcdScreenLines = [
                        'POST TRANSACTION ERROR',
                        error.message,
                        'CHECK CONSOLE / SYSTEM LOGS'
                    ];
                })
                .finally(() => {
                    this.isScanning = false;
                });
            },

            refreshLogs() {
                this.isRefreshingLogs = true;
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContainer = doc.getElementById('logs-table-container');
                        if (newContainer) {
                            document.getElementById('logs-table-container').innerHTML = newContainer.innerHTML;
                        }
                    })
                    .catch(e => console.error('Failed to refresh logs:', e))
                    .finally(() => {
                        this.isRefreshingLogs = false;
                    });
            }
        };
    }
</script>
@endpush
