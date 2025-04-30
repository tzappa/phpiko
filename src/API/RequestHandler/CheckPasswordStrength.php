<?php

declare(strict_types=1);

namespace API\RequestHandler;

use App\Users\Password\PasswordStrength;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * API endpoint for checking password strength
 */
class CheckPasswordStrength implements RequestHandlerInterface
{
    public function __construct(private PasswordStrength $passwordStrength) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getBody();
        if (empty($data)) {
            return new JsonResponse([
                'score' => 0,
                'feedback' => 'No data provided',
                'strengthLabel' => 'Error',
                'isStrong' => false
            ]);
        }
        $data = json_decode((string) $data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
                'score' => 0,
                'feedback' => 'Invalid JSON data',
                'strengthLabel' => 'Error',
                'isStrong' => false
            ]);
        }
        $password = $data['password'] ?? '';
        $password = trim($password);
        if (empty($password)) {
            return new JsonResponse([
                'score' => 0,
                'feedback' => 'Password is required',
                'strengthLabel' => 'Error',
                'isStrong' => false
            ]);
        }

        $strengthDetails = $this->passwordStrength->getStrengthDetails($password);
        $score = $strengthDetails['score'];

        $strengthLabels = [
            0 => 'Very Weak',
            1 => 'Weak',
            2 => 'Medium',
            3 => 'Strong',
            4 => 'Very Strong'
        ];

        $feedback = $strengthDetails['feedback']['warning'] ?? '';
        if (empty($feedback)) {
            $feedback = $strengthLabels[$score];
        }

        $suggestions = $strengthDetails['feedback']['suggestions'] ?? [];

        return new JsonResponse([
            'score' => $score,
            'strengthLabel' => $strengthLabels[$score],
            'feedback' => $feedback,
            'suggestions' => $suggestions,
            'isStrong' => $this->passwordStrength->isStrong($password)
        ]);
    }
}
