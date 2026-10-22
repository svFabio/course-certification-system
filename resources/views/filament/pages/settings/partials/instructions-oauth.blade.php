<div class="space-y-2 text-sm text-gray-600">
    <p class="font-semibold">Cuenta Google personal:</p>
    <ol class="list-decimal list-inside space-y-1">
        <li>En Google Cloud Console, cree credenciales <strong>OAuth 2.0 Client ID</strong></li>
        <li>Tipo de aplicación: <strong>Web application</strong></li>
        <li>Authorized redirect URIs: <code>{{ route('google-sheets.callback') }}</code></li>
        <li>Copie el Client ID y Client Secret en <code>.env</code></li>
        <li>Haga clic en "Conectar con Google" arriba</li>
    </ol>
</div>
