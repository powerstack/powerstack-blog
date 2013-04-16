<?php
require_once(dirname(__FILE__) . '/includes/auth.php');
$database = new Powerstack\Plugins\Database();

// home page
$app->get('/', function($request, $params) {
    global $database;

    if (!isset($params->p)) {
        $offset = (0 * 10);
    } else {
        $offset = ((int) $params->p * 10);
    }

    $result = $database->executeSql("
        SELECT u.name as name, p.title as title, p.content as content, p.tags as tags, p.id as id
        FROM posts p
        INNER JOIN users u ON u.id = p.author
        ORDER BY p.created DESC
        LIMIT 10
        OFFSET " . $offset . "
    ");

    if (!$result) {
        template('error.tpl');
    } else {
        $rows = $database->fetchAll();
        $database->executeSql("SELECT COUNT(id) as count FROM posts");
        $count = $database->fetch();

        template('home.tpl', array('post' => $rows, 'page' => !isset($params->p) ? 0 : $params->p, 'count' => $count->count));
    }
});

// user login
$app->get('/user/login', function($request, $params) {
    if (authd()) {
        redirect('/');
    }

    template('user-login.tpl');
});

// user logout
$app->get('/user/logout', function($request, $params) {
    $request->session->delete('user');
    redirect('/');
});

// setup admin account
$app->get('/admin', function($request, $params) {
    if (file_exists('installed')) {
        redirect('/');
    }

    template('admin.tpl');
});

// show list of posts
$app->get('/post/list', function($request, $params) {
    if (!authd() && !hasRole()) {
        redirect('/');
    }
});

// create post
$app->get('/post/create', function($request, $params) {
    if (!authd() && !hasRole('admin')) {
        redirect('/');
    }

    template('post-create.tpl');
});

// edit post
$app->get('/post/edit/:id', function($request, $params) {
    if (!authd() && !hasRole('admin')) {
        redirect('/');
    }

    template('post-edit.tpl');
});

// delete post
$app->get('/post/delete/:id', function($request, $params) {
    if (!authd() && !hasRole('admin')) {
        redirect('/');
    }

    template('post-delete.tpl');
});

// show list of comments
$app->get('/comments', function($request, $params) {
    if (!authd() && !hasRole('admin')) {
        redirect('/');
    }

    template('comments.tpl');
});

// delete comment
$app->get('/comments/delete/:id', function($request, $params) {
    if (!authd() && !haseRole('admin')) {
        redirect('/');
    }

    template('comments-delete.tpl');
});

// view a post
$app->get('/:year/:month/:title', function($request, $params) {
    global $database;

    $start = strtotime($params->year . '-' . $params->month . '-01');
    $finish = strtotime($params->year . '-' . ($params->month + 1) . '-01');

    $result = $database->executeSql(
        "SELECT id FROM posts WHERE created >= ? AND created < ? AND title = ?",
        array($start, $finish, str_replace('-', ' ', $params->title))
    );

    if (!$result) {
        template('error.tpl');
    } else {
        $row = $database->fetch();

        if (empty($row)) {
            template('post.tpl');
        } else {
            $database->executeSql("
                SELECT u.name as name, p.title as title, p.content as content, p.tags as tags, p.id as id
                FROM posts p
                INNER JOIN users u ON u.id = p.author
                WHERE p.id = ?
            ", array($row->id));

            $post = $database->fetch();

            $database->executeSql("
                SELECT c.name as name, c.content as content, c.created as created FROM comments c
                INNER JOIN posts p ON p.id = c.post
                WHERE p.id = ?
            ", array($row->id));

            $comments = $database->fetchAll();

            template('post.tpl', array('post' => $post, 'comments' => $comments));
        }
    }
});

// hook before_template_render
// Add AUTHENICATED variable to all templates
hook('before_template_render', function($params) {
    $params['AUTHENICATED'] = authd();
    return $params;
});
?>
