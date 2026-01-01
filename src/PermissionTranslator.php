<?php

namespace Deifhelt\LaravelPermissionsManager;

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

        if (Lang::has("permissions::permissions.special.{$normalized}")) {
            return trans("permissions::permissions.special.{$normalized}");
        }
        if (Lang::has("permissions::permissions.dictionary.{$normalized}")) {
            return trans("permissions::permissions.dictionary.{$normalized}");
        }

        $parts = explode(' ', $normalized);

        if (count($parts) < 2) {
            return Lang::has("permissions::permissions.actions.{$normalized}")
                ? trans("permissions::permissions.actions.{$normalized}")
                : (Lang::has("permissions::permissions.resources.{$normalized}")
                    ? trans("permissions::permissions.resources.{$normalized}")
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
        if (Lang::has("permissions::permissions.actions.{$action}")) {
            return trans("permissions::permissions.actions.{$action}");
        }
        if (Lang::has("permissions::permissions.dictionary.{$action}")) {
            return trans("permissions::permissions.dictionary.{$action}");
        }

        return $action;
    }

    protected static function translateResource(string $resource): string
    {
        if (Lang::has("permissions::permissions.resources.{$resource}")) {
            return trans("permissions::permissions.resources.{$resource}");
        }
        if (Lang::has("permissions::permissions.dictionary.{$resource}")) {
            return trans("permissions::permissions.dictionary.{$resource}");
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
            if (Lang::has("permissions::permissions.resources.{$token}")) {
                $trans = trans("permissions::permissions.resources.{$token}");
            } elseif (Lang::has("permissions::permissions.dictionary.{$token}")) {
                $trans = trans("permissions::permissions.dictionary.{$token}");
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
