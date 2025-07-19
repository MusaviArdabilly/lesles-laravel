<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
            body { font-family: "Roboto", sans-serif; }
            .h-dvh { height: 100dvh; }
            .flex { display: flex; }
            .justify-center { justify-content: center; }
            .items-center { align-items: center; }
            .text-2xl { font-size: 1.5rem; line-height: 2rem; }
            .font-bold { font-weight: 700; }
            .text-green-600 { color: #16a34a; }
            .mb-4 { margin-bottom: 1rem; }
            .text-gray-700 { color: #374151; }
            .text-gray-500 { color: #6b7280; }
            .mt-2 { margin-top: 0.5rem; }
            .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
            .bg-white { background-color: #ffffff; }
            .p-6 { padding: 1.5rem; }
            .rounded-lg { border-radius: 0.5rem; }
            .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); }
            .text-center { text-align: center; }
            .text-cyan-400 { color: rgb(34 211 238 / var(--tw-text-opacity, 1)); }
        </style>

        <!-- Auto Close and Redirect  -->
         
        <script>
            // Redirect after 3 seconds
            setTimeout(() => {
            // Try to close the window
            window.close();

            // If on mobile cant close 
            window.location.href = "http://lesles.id/complete-teacher-profile";
            }, 7000);
        </script>
    </head>
    <body>
        <div class="flex h-dvh justify-center items-center">
            <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                <h1 class="text-2xl font-bold text-cyan-400 mb-4">Email Kamu Udah Terverifikasi 🎉</h1>
                <p class="text-gray-700">Yeay! Email kamu berhasil diverifikasi.</p>
                <p class="text-gray-700">Makasih udah konfirmasi ya~</p>
                <p class="text-gray-500 mt-2 text-sm">Halaman ini bakal nutup otomatis... atau kamu bisa tutup sekarang 😎</p>
            </div>
        </div>
    </body>
</html>
