<?php

declare(strict_types=1);

namespace Test\Config\Parser;

use Clear\Config\Parser\AbstractFileReader;
use Clear\Config\Parser\ParserInterface;
use Clear\Config\Exception\FileException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AbstractFileReader class.
 */
#[CoversClass(AbstractFileReader::class)]
class AbstractFileReaderTest extends TestCase
{
    private $testParser;

    protected function setUp(): void
    {
        // Create a concrete implementation of AbstractFileReader for testing
        $this->testParser = new class extends AbstractFileReader {
            public function fromString(string $string): array
            {
                return ['parsed' => $string];
            }
        };
    }

    public function testAbstractFileReaderImplementsParserInterface()
    {
        $this->assertInstanceOf(ParserInterface::class, $this->testParser);
    }

    public function testFromFileWithValidFile()
    {
        // Create a temporary file with test content
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        $testContent = 'test content';
        file_put_contents($tempFile, $testContent);

        try {
            $result = $this->testParser->fromFile($tempFile);

            $this->assertIsArray($result);
            $this->assertSame('test content', $result['parsed']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithNonExistentFile()
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Could not find configuration file /non/existent/file.txt');

        $this->testParser->fromFile('/non/existent/file.txt');
    }

    public function testFromFileWithDirectory()
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Could not find configuration file /tmp');

        $this->testParser->fromFile('/tmp');
    }

    public function testFromFileWithUnreadableFile()
    {
        // Create a temporary file and make it unreadable
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        file_put_contents($tempFile, 'test content');
        chmod($tempFile, 0000);

        try {
            $this->expectException(FileException::class);
            $this->expectExceptionMessage("Configuration file {$tempFile} is unreadable");

            $this->testParser->fromFile($tempFile);
        } finally {
            chmod($tempFile, 0644);
            unlink($tempFile);
        }
    }

    public function testFromFileWithEmptyFile()
    {
        // Create a temporary empty file
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        file_put_contents($tempFile, '');

        try {
            $result = $this->testParser->fromFile($tempFile);

            $this->assertIsArray($result);
            $this->assertSame('', $result['parsed']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithLargeFile()
    {
        // Create a temporary file with large content
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        $largeContent = str_repeat('test content ', 1000);
        file_put_contents($tempFile, $largeContent);

        try {
            $result = $this->testParser->fromFile($tempFile);

            $this->assertIsArray($result);
            $this->assertSame($largeContent, $result['parsed']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithBinaryFile()
    {
        // Create a temporary file with binary content
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.bin';
        $binaryContent = "\x00\x01\x02\x03\x04\x05";
        file_put_contents($tempFile, $binaryContent);

        try {
            $result = $this->testParser->fromFile($tempFile);

            $this->assertIsArray($result);
            $this->assertSame($binaryContent, $result['parsed']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithFileContainingNullBytes()
    {
        // Create a temporary file with null bytes
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        $contentWithNulls = "test\0content\0with\0nulls";
        file_put_contents($tempFile, $contentWithNulls);

        try {
            $result = $this->testParser->fromFile($tempFile);

            $this->assertIsArray($result);
            $this->assertSame($contentWithNulls, $result['parsed']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithFileContainingNewlines()
    {
        // Create a temporary file with various newline characters
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        $contentWithNewlines = "line1\nline2\rline3\r\nline4";
        file_put_contents($tempFile, $contentWithNewlines);

        try {
            $result = $this->testParser->fromFile($tempFile);

            $this->assertIsArray($result);
            $this->assertSame($contentWithNewlines, $result['parsed']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithFileContainingSpecialCharacters()
    {
        // Create a temporary file with special characters
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        $specialContent = "Special chars: éñü中文😀\t\n\r";
        file_put_contents($tempFile, $specialContent);

        try {
            $result = $this->testParser->fromFile($tempFile);

            $this->assertIsArray($result);
            $this->assertSame($specialContent, $result['parsed']);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithSymlink()
    {
        // Create a temporary file and a symlink to it
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        $symlinkFile = $tempFile . '.link';
        file_put_contents($tempFile, 'test content');
        symlink($tempFile, $symlinkFile);

        try {
            $result = $this->testParser->fromFile($symlinkFile);

            $this->assertIsArray($result);
            $this->assertSame('test content', $result['parsed']);
        } finally {
            unlink($symlinkFile);
            unlink($tempFile);
        }
    }

    public function testFromFileWithFileThatCannotBeRead()
    {
        // This test simulates a file that exists but cannot be read
        // We'll create a file and then remove read permissions
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        file_put_contents($tempFile, 'test content');

        // Make the file unreadable
        chmod($tempFile, 0000);

        try {
            $this->expectException(FileException::class);
            $this->expectExceptionMessage("Configuration file {$tempFile} is unreadable");

            $this->testParser->fromFile($tempFile);
        } finally {
            // Restore permissions and clean up
            chmod($tempFile, 0644);
            unlink($tempFile);
        }
    }

    public function testFromFileWithFileThatReturnsFalseFromFileGetContents()
    {
        // This is a bit tricky to test as file_get_contents rarely returns false
        // We'll create a mock scenario by using a file that gets deleted between
        // the is_file check and the file_get_contents call
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        file_put_contents($tempFile, 'test content');

        // Create a custom parser that deletes the file after is_file check
        $customParser = new class extends AbstractFileReader {
            private $tempFile;

            public function setTempFile($file)
            {
                $this->tempFile = $file;
            }

            public function fromString(string $string): array
            {
                return ['parsed' => $string];
            }

            public function fromFile(string $fileName): array
            {
                if (!is_file($fileName)) {
                    throw new FileException("Could not find configuration file {$fileName}");
                }
                if (!is_readable($fileName)) {
                    throw new FileException("Configuration file {$fileName} is unreadable");
                }

                // Delete the file to simulate file_get_contents returning false
                if ($fileName === $this->tempFile) {
                    unlink($fileName);
                }

                $contents = file_get_contents($fileName);
                if ($contents === false) {
                    throw new FileException("Could not read configuration file {$fileName}");
                }

                return $this->fromString($contents);
            }
        };

        $customParser->setTempFile($tempFile);

        try {
            $this->expectException(FileException::class);
            $this->expectExceptionMessage("Could not read configuration file {$tempFile}");

            $customParser->fromFile($tempFile);
        } catch (\Exception $e) {
            // Clean up if the file still exists
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw $e;
        }
    }
}
