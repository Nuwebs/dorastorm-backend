<?php
namespace App\Utils;

use App\Exceptions\FileUploadException;
use Exception;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileUpload
{
    protected string $requestFileKey;
    protected string $folder;
    protected string $disk;
    protected bool $expectsArray;

    /**
     * @var array<string, string> $extraValidationRules
     */
    protected array $extraValidationRules;

    /**
     * @param array<string, string> $extraValidationRules
     */
    public function __construct(
        string $folder,
        string $disk,
        bool $expectsArray = false,
        string $requestFileKey = 'file',
        array $extraValidationRules = [],
    ) {
        $this->folder = $folder;
        $this->disk = $disk;
        $this->requestFileKey = $requestFileKey;
        $this->expectsArray = $expectsArray;
        $this->extraValidationRules = $extraValidationRules;
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    public function getImageValidationRules(): array
    {
        $rule = 'image|max:' . config('filesystems.max_file_size.image');
        return $this->makeValidations($rule);
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    public function getDocumentValidationRules(): array
    {
        $mimes = MimeType::DOCUMENTS->value;
        $rule = "file|mimetypes:$mimes|max:" . config('filesystems.max_file_size.document');
        return $this->makeValidations($rule);
    }

    /**
     * @param UploadedFile|array<UploadedFile>|null $files
     * @return array<string>|string The function will return an array if the $files parameter
     * is an array, string otherwise
     */
    public function upload(
        UploadedFile|array|null $files,
        bool $array = false
    ): array|string {
        if (is_null($files)) {
            throw new FileUploadException('The element to be uploaded must not be null');
        }
        if (is_array($files)) {
            return $this->multipleFileStore($files);
        }
        try {
            return $this->fileStore($files);
        } catch (FileUploadException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    private function makeValidations(string $eachFileRules): array
    {
        if ($this->expectsArray) {
            return array_merge($this->extraValidationRules, [
                $this->requestFileKey => 'required|array',
                "$this->requestFileKey.*" => $eachFileRules
            ]);
        }
        return array_merge($this->extraValidationRules, [$this->requestFileKey => $eachFileRules]);
    }

    private function fileStore(UploadedFile $file): string
    {
        $path = $file->store($this->folder, $this->disk);
        if ($path === false)
            throw new FileUploadException('The file could not be uploaded');
        return $path;
    }

    /**
     * @param array<UploadedFile> $files
     * @return array<string>
     */
    private function multipleFileStore(array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            try {
                $paths[$file->getClientOriginalName()] = $this->fileStore($file);
            } catch (FileUploadException) {
                $paths[$file->getClientOriginalName()] = null;
            }
        }
        return $paths;
    }
}
