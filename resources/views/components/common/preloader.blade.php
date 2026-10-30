<div
  x-show="loaded"
  x-init="if (document.readyState !== 'loading') { setTimeout(() => loaded = false, 100); } else { window.addEventListener('DOMContentLoaded', () => { setTimeout(() => loaded = false, 150); }); setTimeout(() => loaded = false, 600); }"
  class="fixed left-0 top-0 z-999999 flex h-screen w-screen items-center justify-center bg-white dark:bg-black"
>
  <div
    class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent"
  ></div>
</div>
