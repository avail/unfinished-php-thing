<?php
require('vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/app.db'
    )
));

$app->register(new Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider(), array(
    'orm.proxies_dir' => __DIR__ . '/proxies',
    'orm.em.options' => array(
        'mappings' => [
            [
                'type' => 'annotation',
                'namespace' => 'Entities',
                'path' => __DIR__ . '/entities'
            ]
        ]
    )
));

// TODO! mod uploading / downloading
// TODO make things not look like shit

$tool = new \Doctrine\ORM\Tools\SchemaTool($app['orm.em']);
$classes = array(
  $app['orm.em']->getClassMetadata('Entities\User'),
  $app['orm.em']->getClassMetadata('Entities\Keys'),
  $app['orm.em']->getClassMetadata('Entities\Mods')
);
$tool->updateSchema($classes);

function validate_key($key) {
    global $app; // evil!

    $query = $app['orm.em']->createQuery('SELECT k from Entities\Keys k WHERE k.key = :key AND k.valid = true');
    $query->setParameter('key', $key);

    $values = $query->getResult();

    return count($values) > 0;
}

$root = $app['controllers_factory'];
$admin = $app['controllers_factory'];

$root->get('/', function () use ($app) {
    return $app['twig']->render('index.twig');
});

$admin->get('/', function () use ($app) {
    return $app['twig']->render('indwtex.twig');
});

$root->get('/register/{key}', function ($key) use ($app) {
    if (!validate_key($key))
    {
        return $app['twig']->render('invalid.twig');
    }

    return $app['twig']->render('register.twig', array('userdata' => array('key' => $key)));
});

$root->get('/register', function() use ($app) {
    // return "no key lol";
    return $app['twig']->render('invalid.twig');
});

$root->post('/register', function() use ($app) {
    $error = array();

    $request = $app['request_stack']->getCurrentRequest();

    $userdata = array();
    $userdata['username'] = $request->get('username');
    $userdata['password'] = $request->get('password');
    $userdata['confirm'] = $request->get('cfpassword');
    $userdata['email'] = $request->get('email');
    $userdata['key'] = $request->get('key');

    if (!validate_key($userdata['key'])) {
        $error[] = 'yo that\'s an invalid key';
    }

    if (empty($userdata['username'])) {
        $error[] = 'No username specified';
    }

    if (strlen($userdata['username']) > 24 || strlen($userdata['username']) < 4) {
        $error[] = 'Username is too long or too short. (min. length is 4, max. length is 24)';
    }

    if (strlen($userdata['password']) > 24 || strlen($userdata['password']) < 8) {
        $error[] = 'Password is too long or too short. (min. length is 8, max. length is 24)';
    }

    if ($userdata['password'] != $userdata['confirm']) {
        $error[] = 'Password confirmation does not match.';
    }

    if (empty($userdata['email'])) {
        $error[] = 'No E-Mail specified.';
    }

    if (!empty($userdata['email']) && !strpos($userdata['email'], '@')) {
        $error[] = 'Invalid E-Mail specified.';
    }

    if (!empty($error)) {
        return $app['twig']->render('register.twig', array('errors' => $error, 'userdata' => $userdata));
    }

    $em = $app['orm.em'];

    $query = $app['orm.em']->createQuery('UPDATE Entities\Keys k SET k.valid = false WHERE k.key = :key');
    $query->setParameter('key', $userdata['key']);
    $query->execute();

    $user = new Entities\User;
    $user->username = $userdata['username'];
    $user->password = password_hash($userdata['password'], PASSWORD_DEFAULT);
    $user->email = $userdata['email'];

    $em->persist($user);
    $em->flush();

    return $app->redirect('/');
});

$root->get('/login', function() use ($app) {
    $session = $app['session']->get('user_id');
    $error = array();

    if (!$session) {
        return $app['twig']->render('login.twig');
    } else {
        $error[] = 'You are already logged in!';
        return $app['twig']->render('invalid.twig', array('errors' => $error));
    }
});

$root->get('/logout', function() use ($app) {
    // NOTE: remove session data here
    $app['session']->remove('user_id');
    $app['session']->remove('username');
});

$root->post('/login', function() use ($app) {
    $error = array();

    $request = $app['request_stack']->getCurrentRequest();

    $userdata = array();
    $userdata['username'] = $request->get('username');
    $userdata['password'] = $request->get('password');

    $em = $app['orm.em'];

    $query = $em->createQuery('SELECT u FROM Entities\User u WHERE u.username = :username');
    $query->setParameter('username', $userdata['username']);
    $results = $query->getResult();

    if (count($results) == 0) {
        $error[] = 'Invalid username specified.';
    } else {
        if (!password_verify($userdata['password'], $results[0]->password)) {
            $error[] = 'Invalid password specified.';
        } else {
            // NOTE: if sesion data is added here it has to be removed on logout above
            $app['session']->set('user_id', $results[0]->id);
            $app['session']->set('username', $results[0]->username);

            return $app->redirect('/');
        }
    }

    return $app['twig']->render('login.twig', array('errors' => $error, 'userdata' => $userdata));
});

$admin->get('/generate', function() use ($app) {
    $em = $app['orm.em'];

    $key = new Entities\Keys;
    $key->key = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
    $key->valid = true;

    $em->persist($key);
    $em->flush();

    return $key->key;
});

$app->mount('/', $root);
$app->mount('/admin', $admin);
$app->run();
?>