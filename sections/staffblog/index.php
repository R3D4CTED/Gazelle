<?php
enforce_login();

if (!check_perms('users_mod')) {
    error(403);
}

$blogMan = new Gazelle\Manager\StaffBlog;
$blogMan->visit($LoggedUser['ID']);

View::show_header('Staff Blog','bbcode');

if (check_perms('admin_manage_blog')) {
    if (!empty($_REQUEST['action'])) {
        switch ($_REQUEST['action']) {
            case 'editblog':
                if ((int)$_GET['id']) {
                    $blogMan->load((int)$_GET['id']);
                }
                break;

            case 'takeeditblog':
                authorize();
                $title = trim($_POST['title'] ?? '');
                if (empty($title)) {
                    error("Please enter a title.");
                }
                $body = trim($_POST['body'] ?? '');
                if (empty($body)) {
                    error("Please enter a body.");
                }
                if ((int)$_POST['blogid']) {
                    $blogMan->setId((int)$_POST['blogid'])
                        ->setTitle(trim($_POST['title']))
                        ->setBody(trim($_POST['body']))
                        ->modify();
                }
                header('Location: staffblog.php');
                exit;

            case 'deleteblog':
                if ((int)$_GET['id']) {
                    authorize();
                    $blogMan->remove((int)$_GET['id']);
                }
                header('Location: staffblog.php');
                exit;

            case 'takenewblog':
                authorize();
                $title = trim($_POST['title'] ?? '');
                if (empty($title)) {
                    error("Please enter a title.");
                }
                $body = trim($_POST['body'] ?? '');
                if (empty($body)) {
                    error("Please enter a body.");
                }
                $blogMan->setTitle($title)
                    ->setBody($body)
                    ->setAuthorId($LoggedUser['ID'])
                    ->modify();
                send_irc("PRIVMSG ".MOD_CHAN." :!mod New staff blog: " . $blogMan->title()
                    . " - https://".SSL_SITE_URL."/staffblog.php#blog" . $blogMan->blogId()
                );
                header('Location: staffblog.php');
                exit;

           default:
                error(403);
                break;
        }
    }
    echo G::$Twig->render('staffblog/edit.twig', [
        'auth' => $LoggedUser['AuthKey'],
        'blog' => $blogMan,
        'verb' => empty($_GET['action']) ? 'create' : 'edit',
        'show_form' => !isset($_REQUEST['action']) || $_REQUEST['action'] != 'editblog',
    ]);
}

echo G::$Twig->render('staffblog/list.twig', [
    'auth'   => $LoggedUser['AuthKey'],
    'editor' => check_perms('admin_manage_blog'),
    'list'   => $blogMan->blogList(),
]);

View::show_footer();
