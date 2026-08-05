<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-xs hover:bg-gray-50 active:bg-gray-100 active:transform-[scale(0.97)] focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 cursor-pointer disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
