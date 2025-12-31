<?php

namespace Deifhelt\LaravelPermissionsManager;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Lang;

class PermissionTranslator
{
    /**
     * Translates a permission to the configured language.
     */
    public static function translate(string $permission): string
    {
        $original = $permission;
        $normalized = strtolower(trim($permission));
        $normalized = str_replace(['.', '-', ':', '/'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        if (Lang::has("permissions.special.{$normalized}")) {
            return trans("permissions.special.{$normalized}");
        }
        if (Lang::has("permissions.dictionary.{$normalized}")) {
            return trans("permissions.dictionary.{$normalized}");
        }

        $parts = explode(' ', $normalized);

        if (count($parts) < 2) {
            return Lang::has("permissions.actions.{$normalized}")
                ? trans("permissions.actions.{$normalized}")
                : (Lang::has("permissions.resources.{$normalized}")
                    ? trans("permissions.resources.{$normalized}")
                    : $original);
        }

        $actionKey = $parts[0];
        $resourceKey = implode(' ', array_slice($parts, 1));

        $translatedAction = self::translateAction($actionKey);
        $translatedResource = self::translateResource($resourceKey);

        if ($translatedAction !== $actionKey || $translatedResource !== $resourceKey) {
            return trim($translatedAction . ' ' . $translatedResource);
        }

        $maybeResourceKey = implode(' ', array_slice($parts, 0, -1));
        $maybeActionKey = end($parts);

        $translatedActionReverse = self::translateAction($maybeActionKey);
        $translatedResourceReverse = self::translateResource($maybeResourceKey);

        if ($translatedActionReverse !== $maybeActionKey || $translatedResourceReverse !== $maybeResourceKey) {
            return trim($translatedActionReverse . ' ' . $translatedResourceReverse);
        }

        return $original;
    }

    protected static function translateAction(string $action): string
    {
        if (Lang::has("permissions.actions.{$action}")) {
            return trans("permissions.actions.{$action}");
        }
        if (Lang::has("permissions.dictionary.{$action}")) {
            return trans("permissions.dictionary.{$action}");
        }

        return $action;
    }

    protected static function translateResource(string $resource): string
    {
        if (Lang::has("permissions.resources.{$resource}")) {
            return trans("permissions.resources.{$resource}");
        }
        if (Lang::has("permissions.dictionary.{$resource}")) {
            return trans("permissions.dictionary.{$resource}");
        }

        return self::translateResourceTokens($resource);
    }

    /**
     * Translates a resource name that is composed of tokens (e.g., "inventory movements") word by word.
     */
    protected static function translateResourceTokens(string $resource): string
    {
        $tokens = preg_split('/[ _]+/', $resource);
        $translatedTokens = [];

        foreach ($tokens as $token) {
            $trans = $token;
            if (Lang::has("permissions.resources.{$token}")) {
                $trans = trans("permissions.resources.{$token}");
            } elseif (Lang::has("permissions.dictionary.{$token}")) {
                $trans = trans("permissions.dictionary.{$token}");
            }
            $translatedTokens[] = $trans;
        }

        $result = implode(' ', $translatedTokens);
        return $result;
    }

    /**
     * Translates a collection of permissions.
     */
    public static function translateMany(iterable $permissions): array
    {
        $translated = [];
        foreach ($permissions as $permission) {
            $translated[$permission] = self::translate($permission);
        }
        return $translated;
    }
}
