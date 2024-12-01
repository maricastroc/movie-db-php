<?php

$user = auth();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$user) {
        abort(403);
    }

    $validations = [];
    $rules = [
        'title' => ['required', 'min:3'],
        'director' => ['required'],
        'synopsis' => ['required'],
        'release_year' => ['required', 'min:4', 'max-4'],
    ];

    $validation = Validation::validate($rules, $_POST);
    $validations = $validation->validations;

    // Verificar se a imagem foi enviada e se não houve erro no upload
    if (empty($_FILES['cover_url']['tmp_name'])) {
        $validations['cover_url'] = 'Movie cover is required.';
    } elseif ($_FILES['cover_url']['error'] !== UPLOAD_ERR_OK) {
        $validations['cover_url'] = 'Failed to upload image.';
    }

    $imageFileType = strtolower(pathinfo($_FILES['cover_url']['name'], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    // Verificar se a extensão da imagem é permitida
    if (!in_array($imageFileType, $allowedTypes)) {
        $validations['cover_url'] = 'Only JPG, JPEG, PNG & GIF files are allowed.';
    }

    if (!empty($validations)) {
        flash()->push('validations', $validations);
        header("Location: /register-movie");
        exit();
    }

    // Definir o diretório onde a imagem será salva
    $dir = "data/movies/images/";

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true); // Criar o diretório se ele não existir
    }

    // Gerar um nome único para a imagem
    $newFileName = md5(rand()); // Você pode ajustar o nome conforme necessário
    $cover_url = $dir . $newFileName . '.' . $imageFileType; // Caminho completo do arquivo

    // Mover o arquivo para o diretório
    if (move_uploaded_file($_FILES['cover_url']['tmp_name'], $cover_url)) {
        // Obter os dados do filme do formulário
        $title = htmlspecialchars($_POST['title']);
        $director = htmlspecialchars($_POST['director']);
        $synopsis = htmlspecialchars($_POST['synopsis']);
        $release_year = htmlspecialchars($_POST['release_year']);

        // Inserir o filme no banco de dados com o caminho da imagem
        $database->query(
            query: "INSERT INTO movies (user_id, title, director, synopsis, release_year, cover_url) 
            VALUES (:user_id, :title, :director, :synopsis, :release_year, :cover_url)",
            class: Rating::class,
            params: [
                'user_id' => $user->id,
                'title' => $title,
                'director' => $director,
                'synopsis' => $synopsis,
                'release_year' => $release_year,
                'cover_url' => $cover_url, // Salvar o caminho completo da imagem no banco
            ]
        );

        flash()->push('message', 'Movie successfully registered!');
    } else {
        flash()->push('validation', 'Failed to upload image.');

        header("Location: /register-movie");
        exit();
    }

    // Redirecionar para a página de filmes do usuário
    header("Location: /my-movies");
    exit();
}

view('register-movie');