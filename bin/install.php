#!/usr/bin/env php
<?php
$tag = $argc > 1 ? $argv[1] : 'master';
$build = date('YmdHis') . '-' . strtr($tag, '/', '-');
$tmp = '/tmp';
$archive = "$tmp/$build.tar.gz";
run('git checkout '.$tag);
run('git pull origin '.$tag);
run('git checkout master');
$release = sprintf(
        "%s by %s@%s (%s) (php %s)\n",
        $build,
        get_current_user(),
        gethostname(),
        getenv('SSH_CONNECTION'),
        phpversion()
    )
    . "Last commit:\n" . `git log -1 origin/$tag` . "\n";
run("mkdir $tmp/$build");
run("git archive --format=tar origin/$tag | (cd $tmp/$build && tar xf -)");
chdir("$tmp/$build");
run("composer install --no-dev");
file_put_contents('RELEASE', $release);
echo "\n\n======================== RELEASE ========================\n\n";
echo $release;
echo "=========================================================\n\n";
chdir($tmp);
run("tar -zcf $archive $build");
run("rm -rf $build");
$deployCmd = "sudo tar -zxf $archive -C /www/release/";
$switchCmd = "sudo ln -sfT /www/release/$build /www/current";
$restartCmd = "sudo service php7.0-fpm restart";
echo "DONE: $archive\n\n";
echo "DEPLOY: $deployCmd\n\n";
run($deployCmd);
echo "SWITCH: $switchCmd\n\n";
run($switchCmd);
echo "Restart: $restartCmd\n\n";
run($restartCmd);
exit(0);

function terminate(string $msg, int $code)
{
    echo $msg;
    exit($code);
}

function run(string $cmd)
{
    passthru($cmd, $ret);
    if ($ret !== 0) {
        terminate("[COMMAND FAILED WITH EXIT CODE $ret]\n", 1);
    }
}