<?php

declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Connector;

/**
 * Pre-3.6.0 name of {@see \JDZ\Authentication\Contract\ConnectorInterface}.
 *
 * 3.6.0 moved the interface to `Contract\` without leaving the old name behind,
 * which broke `implements` / type hints written against 3.5. This alias makes
 * both names the same interface again, so old and new code mix freely.
 *
 * @deprecated 3.6.7 use \JDZ\Authentication\Contract\ConnectorInterface; removed in 4.0.0.
 */
\class_alias(\JDZ\Authentication\Contract\ConnectorInterface::class, __NAMESPACE__ . '\ConnectorInterface');
