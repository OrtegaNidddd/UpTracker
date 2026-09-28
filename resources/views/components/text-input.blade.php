@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10 rounded-xl shadow-sm text-slate-800 placeholder-slate-400 text-sm transition-all']) }}>
