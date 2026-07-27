<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Offline {@see CURLRequest} double that records the URL and options of the
 * last `post()` before returning a canned response.
 *
 * Recording rather than ignoring the arguments is deliberate: it lets a test
 * pin the endpoint and the exact SOAP body the provider sends, so a silent
 * change to either is caught instead of shipping green under a double that
 * accepts anything.
 *
 * The real constructor needs the curl extension plus a live App config; this
 * override skips it entirely, which is what keeps the suite offline.
 */
final class RecordingCurlRequest extends CURLRequest
{
    public string $lastUrl = '';

    /** @var array<string, mixed> */
    public array $lastOptions = [];

    public int $calls = 0;

    /**
     * @param ResponseInterface|null $canned Response to return; `null` makes every
     *                                       call throw, standing in for a network failure.
     */
    public function __construct(private readonly ?ResponseInterface $canned = null)
    {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $url, array $options = []): ResponseInterface
    {
        $this->calls++;
        $this->lastUrl     = $url;
        $this->lastOptions = $options;

        if ($this->canned === null) {
            throw new RuntimeException('Simulated network failure.');
        }

        return $this->canned;
    }
}
