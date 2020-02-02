<?php

class MovieController extends Controller
{
    private $model;
    private $movie;
    private $auth;
    private $helper;

    public function __construct()
    {
        $this->model = $this->model('movie');
        $this->auth = $this->helper('auth');
        $this->helper = $this->helper('upload');

        $this->movie = [
            'id' => !empty($_POST['id']) ? $_POST['id'] : 0,
            'cover_image' => $_FILES['cover_image'],
            'name' => $_POST['name'],
            'genre' => $_POST['genre'],
            'price' => $_POST['price']
        ];
    }

    public function display(int $id)
    {
        $this->movie['id'] = $id;
        $data = $this->model->read($this->movie);

        if ($data->num_rows > 0) {
            while ($row = $data->fetch_assoc()) {
                $fb = $row;
            }
        } else {
            $fb = [];
            $_SESSION['fb'] = 'The selected movie doesn\'t exist';
        }

        $this->view('movie', $fb);
    }

    public function add()
    {
        if (!empty($_SESSION['user']) || !empty($_COOKIE['user'])) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                if ($this->auth->fields($this->movie)) {
                    $name = $this->movie['name'];
                    $data = $this->model->read($this->movie);

                    if ($data->num_rows > 0) {
                        while ($row = $data->fetch_assoc()) {
                            if ($name == $row['name']) {
                                $_SESSION['fb'] = 'Movie ' . $name . ' already exists';
                                header('location: /');
                            }
                        }
                    } else {
                        if ($this->helper->upload_image($this->movie['cover_image'], $name)) {
                            $this->movie['cover_image'] = $this->helper->fb;

                            if ($this->model->create($this->movie)) {
                                $_SESSION['fb'] = 'Successfully added movie';
                                header('location: /');
                            } else {
                                $_SESSION['fb'] = 'Error adding movie';
                                header('location: /');
                            }
                        } else {
                            $_SESSION['fb'] = 'File upload error: ' . $this->helper->fb;
                            header('location: /');
                        }
                    }
                } else {
                    $_SESSION['fb'] = $this->auth->fb;
                    header('location: /');
                }
            } else {
                exit('Invalid request');
            }
        } else {
            $_SESSION['fb'] = 'You need to be logged in to perform this action';
            header('location: /');
        }
    }

    public function edit(int $id)
    {
        if (!empty($_SESSION['user']) || !empty($_COOKIE['user'])) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                if ($this->auth->fields($this->movie)) {

                    $this->movie['id'] = $id;
                    $data = $this->model->read($this->movie);
                    $row = $data->fetch_assoc();

                    if (!empty($this->movie['cover_image']['tmp_name'])) {
                        $this->helper->remove_image($row['cover_image']);

                        if ($this->helper->upload_image($this->movie['cover_image'], $this->movie['name'])) {
                            $this->movie['cover_image'] = $this->helper->fb;
                            if ($this->model->edit($this->movie)) {
                                $_SESSION['fb'] = 'Updated movie successfully';
                                header('location: /');
                            } else {
                                $_SESSION['fb'] = 'Error updating.';
                                header('location: /movie/edit/' . $id);
                            }
                        } else {
                            $_SESSION['fb'] = 'Error updating profile photo: ' . $this->helper->fb;
                        }
                    } else {
                        $this->movie['cover_image'] = $row['cover_image'];

                        if ($this->model->edit($this->movie)) {
                            $_SESSION['fb'] = 'Updated movie successfully';
                            header('location: /');
                        } else {
                            $_SESSION['fb'] = 'Error updating.';
                            header('location: /movie/edit/' . $id);
                        }
                    }
                } else {
                    $_SESSION['fb'] = $this->auth->fb;
                    header('location: /movie/edit/' . $id);
                }
            } else {
                $this->movie['id'] = $id;

                $data = $this->model->read($this->movie);

                if ($data->num_rows > 0) {
                    while ($row = $data->fetch_assoc()) {
                        $fb = $row;
                    }
                }

                $this->view('home', $fb);
            }
        } else {
            $_SESSION['fb'] = 'You need to be logged in to perform this action';
            header('location: /');
        }
    }

    public function delete(int $id)
    {
        if (!empty($_SESSION['user']) || !empty($_COOKIE['user'])) {
            $this->movie['id'] = $id;
            $data = $this->model->read($this->movie);
            $row = $data->fetch_assoc();

            if ($this->helper->remove_image($row['cover_image'])) {
                if ($this->model->delete($id)) {
                    $_SESSION['fb'] = 'Movie deleted';
                    header('location: /');
                } else {
                    $_SESSION['fb'] = 'Error deleting.';
                    header('location: /');
                }
            } else {
                $_SESSION['fb'] = 'Error deleting cover image: ' . $this->helper->fb;
                header('location: /');
            }
        } else {
            $_SESSION['fb'] = 'You need to be logged in to perform this action';
            header('location: /');
        }
    }
}