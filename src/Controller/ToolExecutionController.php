<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

final class ToolExecutionController extends AbstractController
{
    #[Route('/api/tools/nmap', name: 'api_tools_nmap', methods: ['POST'])]
    public function runNmap(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_USER_ADMIN);

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse([
                'error' => 'Invalid JSON payload received.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $target = isset($payload['target']) ? trim((string) $payload['target']) : '';
        $flagsInput = isset($payload['flags']) ? (string) $payload['flags'] : '';

        if ($target === '') {
            return new JsonResponse([
                'error' => 'Target is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match('/^[A-Za-z0-9.\-_:\/,*]+$/', $target)) {
            return new JsonResponse([
                'error' => 'Invalid characters detected in target.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $flags = [];
        foreach (preg_split('/\s+/', trim($flagsInput)) ?: [] as $flag) {
            if ($flag === '') {
                continue;
            }

            if (!preg_match('/^[A-Za-z0-9=:\-.,\/]+$/', $flag)) {
                return new JsonResponse([
                    'error' => sprintf('Flag "%s" contains unsupported characters.', $flag),
                ], Response::HTTP_BAD_REQUEST);
            }

            $flags[] = $flag;
        }

        // Limit the number of flags to mitigate extremely long command invocations.
        if (\count($flags) > 12) {
            return new JsonResponse([
                'error' => 'Too many flags were provided. Please reduce the list and try again.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $command = array_merge(['nmap'], $flags, [$target]);

        if (\function_exists('set_time_limit')) {
            @\set_time_limit(300);
        }

        $process = new Process($command);
        $process->setTimeout(240);
        $process->setIdleTimeout(240);

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            $process->stop();

            return new JsonResponse([
                'error' => 'Nmap execution timed out before completing.',
                'timeout' => true,
            ], Response::HTTP_REQUEST_TIMEOUT);
        }

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();

        $response = [
            'exitCode' => $process->getExitCode(),
            'command' => $command,
            'stdout' => $output,
            'stderr' => $errorOutput,
        ];

        if (!$process->isSuccessful()) {
            $response['error'] = 'Nmap completed with a non-zero exit code.';

            return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($response);
    }
}
