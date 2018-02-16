<?php
/**
 * @package    Grav\Framework\Psr7
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Framework\Psr7;

use Grav\Framework\Uri\UriFilter;
use Grav\Framework\Uri\UriHelper;
use Psr\Http\Message\UriInterface;

/**
 * Bare minimum PSR7 implementation.
 *
 * @package Grav\Framework\Uri\Psr7
 */
abstract class AbstractUri implements UriInterface
{
    /** @var string Uri scheme. */
    private $scheme = '';

    /** @var string Uri user. */
    private $user = '';

    /** @var string Uri password. */
    private $password = '';

    /** @var string Uri host. */
    private $host = '';

    /** @var int|null Uri port. */
    private $port;

    /** @var string Uri path. */
    private $path = '';

    /** @var string Uri query string (without ?). */
    private $query = '';

    /** @var string Uri fragment (without #). */
    private $fragment = '';

    /**
     * Please define constructor which calls $this->init().
     */
    abstract public function __construct();

    /**
     * @inheritdoc
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @inheritdoc
     */
    public function getAuthority()
    {
        $authority = $this->host;

        $userInfo = $this->getUserInfo();
        if ($userInfo !== '') {
            $authority = $userInfo . '@' . $authority;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * @inheritdoc
     */
    public function getUserInfo()
    {
        $userInfo = $this->user;

        if ($this->password !== '') {
            $userInfo .= ':' . $this->password;
        }

        return $userInfo;
    }

    /**
     * @inheritdoc
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @inheritdoc
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @inheritdoc
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @inheritdoc
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @inheritdoc
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @inheritdoc
     */
    public function withScheme($scheme)
    {
        $scheme = UriFilter::filterScheme($scheme);

        if ($this->scheme === $scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->unsetDefaultPort();
        $new->validate();

        return $new;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function withUserInfo($user, $password = '')
    {
        $user = UriFilter::filterUserInfo($user);
        $password = UriFilter::filterUserInfo($password);

        if ($this->user === $user && $this->password === $password) {
            return $this;
        }

        $new = clone $this;
        $new->user = $user;
        $new->password = $user !== '' ? $password : '';
        $new->validate();

        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withHost($host)
    {
        $host = UriFilter::filterHost($host);

        if ($this->host === $host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        $new->validate();

        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withPort($port)
    {
        $port = UriFilter::filterPort($port);

        if ($this->port === $port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;
        $new->unsetDefaultPort();
        $new->validate();

        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withPath($path)
    {
        $path = UriFilter::filterPath($path);

        if ($this->path === $path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;
        $new->validate();

        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withQuery($query)
    {
        $query = UriFilter::filterQueryOrFragment($query);

        if ($this->query === $query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function withFragment($fragment)
    {
        $fragment = UriFilter::filterQueryOrFragment($fragment);

        if ($this->fragment === $fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUrl();
    }

    /**
     * @return array
     */
    protected function getParts()
    {
        return [
            'scheme'    => $this->scheme,
            'host'      => $this->host,
            'port'      => $this->port,
            'user'      => $this->user,
            'pass'      => $this->password,
            'path'      => $this->path,
            'query'     => $this->query,
            'fragment'  => $this->fragment
        ];
    }

    /**
     * Return the fully qualified base URL ( like http://getgrav.org ).
     *
     * Note that this method never includes a trailing /
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        $uri = '';

        $scheme = $this->getScheme();
        if ($scheme !== '') {
            $uri .= $scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '' || $scheme === 'file') {
            $uri .= '//' . $authority;
        }

        return $uri;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        $uri = $this->getBaseUrl() . $this->getPath();

        $query = $this->getQuery();
        if ($query !== '') {
            $uri .= '?' . $query;
        }

        $fragment = $this->getFragment();
        if ($fragment !== '') {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    /**
     * @return string
     */
    protected function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    protected function getPassword()
    {
        return $this->password;
    }

    /**
     * @param array $parts
     * @throws \InvalidArgumentException
     */
    protected function initParts(array $parts)
    {
        $this->scheme = isset($parts['scheme']) ? UriFilter::filterScheme($parts['scheme']) : '';
        $this->user = isset($parts['user']) ? UriFilter::filterUserInfo($parts['user']) : '';
        $this->password = isset($parts['pass']) ? UriFilter::filterUserInfo($parts['pass']) : '';
        $this->host = isset($parts['host']) ? UriFilter::filterHost($parts['host']) : '';
        $this->port = isset($parts['port']) ? UriFilter::filterPort((int)$parts['port']) : null;
        $this->path = isset($parts['path']) ? UriFilter::filterPath($parts['path']) : '';
        $this->query = isset($parts['query']) ? UriFilter::filterQueryOrFragment($parts['query']) : '';
        $this->fragment = isset($parts['fragment']) ? UriFilter::filterQueryOrFragment($parts['fragment']) : '';

        $this->unsetDefaultPort();
        $this->validate();
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function validate()
    {
        if ($this->host === '' && ($this->scheme === 'http' || $this->scheme === 'https')) {
            throw new \InvalidArgumentException('Uri with a scheme must have a host');
        }

        if ($this->getAuthority() === '') {
            if (0 === strpos($this->path, '//')) {
                throw new \InvalidArgumentException('The path of a URI without an authority must not start with two slashes \'//\'');
            }
            if ($this->scheme === '' && false !== strpos(explode('/', $this->path, 2)[0], ':')) {
                throw new \InvalidArgumentException('A relative URI must not have a path beginning with a segment containing a colon');
            }
        } elseif (isset($this->path[0]) && $this->path[0] !== '/') {
            throw new \InvalidArgumentException('The path of a URI with an authority must start with a slash \'/\' or be empty');
        }
    }

    private function unsetDefaultPort()
    {
        if ($this->port !== null && UriHelper::isDefaultPort($this)) {
            $this->port = null;
        }
    }
}
