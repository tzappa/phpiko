<?php

declare(strict_types=1);

namespace Web\RequestHandler;

use Clear\Avatar\Avatar as AvatarCreator;
use Clear\Transformations\Initials;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Echoes user avatar
 */
final class Avatar
{
    public function handle(ServerRequestInterface $request): void
    {
        header('Content-Type: image/png');

        $params = $request->getQueryParams();
        // This is for debug - random initials
        if (empty($params['name'])) {
            $alphabet = explode(',', 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,');
            $name = $alphabet[mt_rand(0, count($alphabet) - 1)] . $alphabet[mt_rand(0, count($alphabet) - 1)];
            $avatar = new AvatarCreator($name);
            // display
            $avatar->make();
            exit;
        }

        $name = $params['name'];
        $name = Initials::fromName($name);

        $filename = dirname(__DIR__, 3) . '/tmp/avatars/' . md5($name) . '.png';
        if (!file_exists($filename)) {
            $avatar = new AvatarCreator($name);
            $avatar->make($filename);
        }
        readfile($filename);
        exit;
    }
}
