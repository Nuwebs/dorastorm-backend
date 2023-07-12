<?php
namespace App\Utils;

enum MimeType: string
{
    case DOCUMENTS = 'text/plain,application/pdf,text/html,text/html,application/xml,text/csv,application/rtf,application/epub+zip,text/markdown,application/x-tex,application/vnd.oasis.opendocument.text,application/vnd.oasis.opendocument.spreadsheet,application/vnd.oasis.opendocument.presentation,application/vnd.oasis.opendocument.graphics,application/vnd.oasis.opendocument.formula,application/vnd.oasis.opendocument.database,application/json,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.template,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.template,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.template,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.slideshow,application/msaccess,application/vnd.ms-outlook,application/x-mspublisher';
    case IMAGES = 'image/jpeg,image/png,image/gif,image/bmp,image/webp,image/tiff,image/svg+xml,image/x-icon';
    case VIDEO = 'video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/x-flv,video/webm,video/3gpp,video/3gpp2,video/x-ms-wmv';
}
