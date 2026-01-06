<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Login...</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500 mx-auto mb-4"></div>
        <p class="text-gray-600">Authenticating with Google...</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Supabase returns the token in the URL fragment (hash) e.g., #access_token=...&...
            const hash = window.location.hash.substring(1);
            const params = new URLSearchParams(hash);
            const accessToken = params.get('access_token');
            const refreshToken = params.get('refresh_token');
            // We might also checking for error in params if user denied access

            if (accessToken) {
                // Send token to backend
                fetch('{{ route('auth.google.exchange') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        access_token: accessToken,
                        refresh_token: refreshToken
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        // Handle server-side error (e.g. user not found for signin)
                         Swal.fire({
                            icon: 'error',
                            title: 'Authentication Failed',
                            text: data.error || 'Unknown error occurred',
                            confirmButtonText: 'Back to Sign In'
                        }).then((result) => {
                             if (result.isConfirmed) {
                                window.location.href = '{{ route('signin') }}';
                             }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                     Swal.fire({
                        icon: 'error',
                        title: 'System Error',
                        text: 'Failed to communicate with the server.',
                         confirmButtonText: 'Back to Sign In'
                    }).then((result) => {
                         if (result.isConfirmed) {
                            window.location.href = '{{ route('signin') }}';
                         }
                    });
                });
            } else {
                // No token found, maybe redirect to login with error
                 Swal.fire({
                    icon: 'error',
                    title: 'Login Error',
                    text: 'No access token received from Google.',
                     confirmButtonText: 'Back to Sign In'
                }).then((result) => {
                     if (result.isConfirmed) {
                        window.location.href = '{{ route('signin') }}';
                     }
                });
            }
        });
    </script>
</body>
</html>
