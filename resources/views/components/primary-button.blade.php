<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-brand-primary hover:bg-blue-600 active:bg-blue-700 text-white font-semibold text-xs uppercase tracking-widest rounded-xl shadow-sm shadow-brand-primary/25 hover:shadow-md transition ease-in-out duration-150 focus:outline-none focus:ring-2 focus:ring-brand-primary/50 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
