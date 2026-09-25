<div class="max-w-md mx-auto card-umss p-8 mt-8">
    <h1 class="text-2xl font-semibold text-[#0E2E5F] text-center">Acceso Institucional</h1>
    <p class="text-sm text-[#4A4A4A] text-center mt-2 mb-6">
        Ingrese con su correo y contraseña. El sistema lo llevará al panel de su rol.
    </p>

    <form wire:submit="submit" class="space-y-4" autocomplete="on">
        <div>
            <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Correo electrónico</label>
            <input type="email"
                wire:model="email"
                name="email"
                autocomplete="username"
                class="input-umss"
                required>
            @error('email')
                <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-medium text-[#4A4A4A] mb-1.5 uppercase tracking-wide">Contraseña</label>
            <input type="password"
                wire:model="password"
                name="password"
                autocomplete="current-password"
                class="input-umss"
                required>
            @error('password')
                <p class="text-[#E01D2E] text-xs mt-1 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-[#4A4A4A]">
            <input type="checkbox" wire:model="remember" class="rounded border-[#E5E5E5]">
            Recordarme
        </label>

        <button type="submit" class="btn-primary w-full !h-11" wire:loading.attr="disabled">
            <span wire:loading.remove>Iniciar Sesión</span>
            <span wire:loading>Ingresando...</span>
        </button>
    </form>
</div>
