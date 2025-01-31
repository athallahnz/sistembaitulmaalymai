<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
	<meta name="author" content="AdminKit">
	<meta name="keywords" content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<link rel="canonical" href="https://demo-basic.adminkit.io/pages-sign-up.html" />

	<title>Sign Up | Baitul Maal Yayasan Masjid Al Iman</title>

	<link href="{{ asset('css/app.css') }}" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
	<main class="d-flex w-100">
		<div class="container d-flex flex-column">
			<div class="row vh-100">
				<div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 mx-auto d-table h-100">
					<div class="d-table-cell align-middle">

						<div class="text-center mt-4">
							<h1 class="h2">Get started</h1>
							<p class="lead">
								Start creating the best possible user experience for your customers.
							</p>
						</div>

						<div class="card">
							<div class="card-body">
								<div class="m-sm-3">
									<form method="POST" action="{{ route('register') }}">
										@csrf
										<div class="mb-3">
											<label class="form-label">Full name</label>
											<input class="form-control form-control-lg" type="text" name="name" placeholder="Enter your name" required />
										</div>
										<div class="mb-3">
											<label class="form-label">Email</label>
											<input class="form-control form-control-lg" type="email" name="email" placeholder="Enter your email" required />
										</div>
										<div class="mb-3">
											<label class="form-label">Nomor</label>
											<input class="form-control form-control-lg" type="text" name="nomor" placeholder="Enter your nomor" required />
										</div>
										<div class="mb-3">
											<label class="form-label">PIN</label>
											<input class="form-control form-control-lg" type="password" name="pin" placeholder="Enter your PIN" required />
										</div>
										<div class="mb-3">
                                            <label for="role" class="form-label">{{ __('Role') }}</label>
                                            <select id="role" name="role"
                                                class="form-control @error('role') is-invalid @enderror" required>
                                                <option value="" disabled selected>Pilih Role</option>
                                                <option value="Admin">Admin</option>
                                                <option value="Ketua Yayasan">Ketua Yayasan</option>
                                                <option value="Bendahara">Bendahara</option>
                                                <option value="Manajer Keuangan">Manajer Keuangan</option>
                                                <option value="Bidang">Bidang</option>
                                            </select>
                                            @error('role')
                                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
										<div class="d-grid gap-2 mt-3">
											<button type="submit" class="btn btn-lg btn-primary">Sign up</button>
										</div>
									</form>
								</div>
							</div>
						</div>
						<div class="text-center mb-3">
							Already have an account? <a href="{{ route('login') }}">Log In</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>

	<script src="{{ asset('js/app.js') }}"></script>

</body>

</html>
