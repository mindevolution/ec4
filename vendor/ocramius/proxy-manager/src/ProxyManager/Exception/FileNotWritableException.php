<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

declare(strict_types=1);

namespace ProxyManager\Exception;

use UnexpectedValueException;

/**
 * Exception for non writable files
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
class FileNotWritableException extends UnexpectedValueException implements ExceptionInterface
{
    public static function fromInvalidMoveOperation(string $fromPath, string $toPath) : self
    {
        return new self(sprintf(
            'Could not move file "%s" to location "%s": '
            . 'either the source file is not readable, or the destination is not writable',
            $fromPath,
            $toPath
        ));
    }

    /**
     * @deprecated this method is unused, and will be removed in ProxyManager 3.0.0
     *
     * @param string $path
     *
     * @return self
     */
    public static function fromNonWritableLocation($path) : self
    {
        $messages    = [];
        $destination = realpath($path);

        if (! $destination) {
            $messages[] = 'path does not exist';
        }

        if ($destination && ! is_file($destination)) {
            $messages[] = 'exists and is not a file';
        }

        if ($destination && ! is_writable($destination)) {
            $messages[] = 'is not writable';
        }

        return new self(sprintf('Could not write to path "%s": %s', $path, implode(', ', $messages)));
    }
}
