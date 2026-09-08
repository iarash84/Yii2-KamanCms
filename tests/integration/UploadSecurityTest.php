<?php

namespace tests\integration;

use common\components\SecureUpload;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\UploadedFile;

class UploadSecurityTest extends TestCase
{
    public function testValidImageIsAccepted(): void
    {
        $temp = $this->tempFile(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $file = $this->uploadedFile('pixel.png', $temp, 'image/png');
        $relative = SecureUpload::storeImage($file);
        $path = Yii::getAlias('@webroot/' . $relative);
        self::assertFileExists($path);
        unlink($path);
    }

    public function testExecutableDisguisedAsImageIsRejected(): void
    {
        $temp = $this->tempFile('<?php echo "unsafe";');
        $file = $this->uploadedFile('unsafe.jpg', $temp, 'image/jpeg');
        $this->expectException(BadRequestHttpException::class);
        SecureUpload::storeImage($file);
    }

    public function testAvatarUsesAnIsolatedGeneratedPathAndCanBeDeleted(): void
    {
        $temp = $this->tempFile(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $file = $this->uploadedFile('personal-name.png', $temp, 'image/png');
        $relative = SecureUpload::storeAvatar($file);
        $path = Yii::getAlias('@webroot/' . $relative);
        self::assertStringStartsWith('upload/avatar/', $relative);
        self::assertStringNotContainsString('personal-name', $relative);
        self::assertFileExists($path);
        SecureUpload::deleteAvatar($relative);
        self::assertFileDoesNotExist($path);
    }

    public function testPdfResumeIsStoredOutsideWebRoot(): void
    {
        $temp = $this->tempFile("%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $file = $this->uploadedFile('resume.pdf', $temp, 'application/pdf');
        $name = SecureUpload::storeResume($file);
        $path = Yii::getAlias('@storage/resumes/' . $name);
        self::assertFileExists($path);
        self::assertStringNotContainsString(Yii::getAlias('@webroot'), $path);
        unlink($path);
    }

    public function testValidMediaFileUsesServerGeneratedName(): void
    {
        $temp = $this->tempFile(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $file = $this->uploadedFile('customer-name.png', $temp, 'image/png');
        $relative = SecureUpload::storeMedia($file);
        self::assertStringStartsWith('upload/media/', $relative);
        self::assertStringNotContainsString('customer-name', $relative);
        $path = Yii::getAlias('@webroot/' . $relative);
        self::assertFileExists($path);
        unlink($path);
    }

    public function testExecutableMediaIsRejected(): void
    {
        $temp = $this->tempFile('<?php echo "unsafe";');
        $file = $this->uploadedFile('unsafe.php', $temp, 'application/x-php');
        $this->expectException(BadRequestHttpException::class);
        SecureUpload::storeMedia($file);
    }

    private function uploadedFile(string $name, string $temp, string $type): UploadedFile
    {
        return new UploadedFile([
            'name' => $name,
            'tempName' => $temp,
            'type' => $type,
            'size' => filesize($temp),
            'error' => UPLOAD_ERR_OK,
        ]);
    }

    private function tempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'yii-upload-test-');
        file_put_contents($path, $content);
        return $path;
    }
}
