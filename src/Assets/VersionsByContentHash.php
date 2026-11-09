<?php

namespace Devletes\ChronoPicker\Assets;

/**
 * Versions an asset by the hash of the file the browser will actually receive.
 *
 * A path repository's package version never changes, so edits would stay cached under it. The published copy is
 * hashed rather than the source: hashing the source names a new version before `filament:assets` has copied it,
 * and a browser that asks in between caches the old contents under the new name.
 */
trait VersionsByContentHash
{
    public function getVersion(): string
    {
        foreach ([$this->getPublicPath(), $this->getPath()] as $path) {
            if (filled($path) && (! $this->isRemote()) && is_file($path)) {
                return substr(md5_file($path) ?: '', 0, 12) ?: parent::getVersion();
            }
        }

        return parent::getVersion();
    }
}
