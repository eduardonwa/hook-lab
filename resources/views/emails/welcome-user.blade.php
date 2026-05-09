<x-mail::message>
# Bienvenido a Hook Lab

Qué onda, {{ $user->name ?? 'bienvenido' }},

Tu cuenta ya está lista.

Hook Lab está hecho para ayudarte a probar hooks, ideas y señales de contenido sin sobrepensar tanto el proceso.

Si tienes preguntas, feedback o algo se siente roto, responde este correo para leerlo.

<x-mail::button :url="url('/home')">
Abrir Hook Lab
</x-mail::button>

Gracias,<br>
Eduardo
</x-mail::message>