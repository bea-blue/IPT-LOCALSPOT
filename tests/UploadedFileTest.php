<?php

use SpotCore\UploadedFile;
use SpotCore\Testing\TestCase;

final class UploadedFileTest extends TestCase
{
    private function temp(string $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ls');
        file_put_contents($path, $bytes);
        return $path;
    }

    public function testTypeComesFromTheBytesNotTheName(): void
    {
        $this->assertSame('pdf', UploadedFile::sniff('%PDF-1.7 rest'));
        $this->assertSame('png', UploadedFile::sniff("\x89PNG\r\n\x1A\n...."));
        $this->assertSame('jpeg', UploadedFile::sniff("\xFF\xD8\xFF\xE0...."));
        $this->assertTrue(UploadedFile::sniff('<?php echo 1;') === null, 'php source detected as a type');
    }

    public function testScriptRenamedAsImageIsRejected(): void
    {
        $path = $this->temp('<?php system($_GET["c"]);');
        $file = new UploadedFile($path, 'permit.jpg', filesize($path), UPLOAD_ERR_OK, false);
        [$type, $error] = $file->check(['jpeg', 'png', 'pdf'], 5 * 1024 * 1024);
        $this->assertTrue($type === null, 'type accepted');
        $this->assertSame('must be JPG, PNG or PDF.', $error);
        @unlink($path);
    }

    public function testPdfPassesAndOversizeFails(): void
    {
        $path = $this->temp("%PDF-1.4\n" . str_repeat('x', 2000));
        $ok = new UploadedFile($path, 'permit.pdf', filesize($path), UPLOAD_ERR_OK, false);
        $this->assertSame('pdf', $ok->check(['pdf'], 5000)[0]);
        $big = new UploadedFile($path, 'permit.pdf', filesize($path), UPLOAD_ERR_OK, false);
        $this->assertSame('is larger than 0 MB.', $big->check(['pdf'], 1000)[1]);
        $this->assertSame('permit.pdf', (new UploadedFile($path, 'C:\\fakepath\\permit.pdf', 1, UPLOAD_ERR_OK, false))->clientName());
        @unlink($path);
    }
}
