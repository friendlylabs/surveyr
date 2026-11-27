<?php

namespace App\Exceptions;

use Exception;

/**
 * ExportFailed
 *
 * An export that can't be made for a reason worth telling the person who asked (too many attachments, none at all,
 * the archive couldn't be written); its message is that reason, put in the email (ExportResponses::failed).
 */
class ExportFailed extends Exception {}
