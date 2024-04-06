<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plakos Moodle Webservices - question type helper
 *
 * @package   local_ws_plakos
 * @copyright 2024 Plakos GmbH <info@plakos.de>
 * @license   TODO
 */

namespace local_ws_plakos\webservice\get_questions;

/**
 * Small helper class to handle question types.
 */
class qtype_helper {

    /**
     * The available types
     */
    const TYPES = [
        'multichoice' => \qtype_multichoice_single_question::class,
    ];

    /**
     * Gets the list of available types
     *
     * @return array
     */
    public function default_types()  : array {
        return array_keys(self::TYPES);
    }

    /**
     * Parses the given comma seperated types to an array.
     *
     * @param string|null $types
     * @return array
     */
    public function parse_from_comma_string(?string $types = null) : array {
        return array_map('strtolower', array_map('trim', explode(',', $types)));
    }

    /**
     * Validates the given types against the existing types.
     *
     * @param array $giventypes
     * @return bool
     */
    public function validate_given(array $giventypes) : bool {
        foreach ($giventypes as $giventype) {
            if (!isset(self::TYPES[$giventype])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Quotes all given types.
     *
     * @param array $types
     * @return array
     */
    public function quote(array $types) : array {
        return array_map(fn($type) => "'" . $type . "'", $types);
    }

    /**
     * Translates a question class to the corresponding type key.
     *
     * @param \question_definition $question
     * @return string|null
     */
    public function translate_type(\question_definition $question) : ?string {
        return array_search(get_class($question), self::TYPES) ?: null;
    }

    /**
     * T4ransforms the given moodle question to an array.
     *
     * @param \question_definition $questionfrombank
     * @return array
     */
    public function to_array(\question_definition $questionfrombank) : array {
        // Build question response.
        $question = [
            'id' => $questionfrombank->id,
            'title' => $questionfrombank->name,
            'type' => $this->translate_type($questionfrombank),
        ];

        return match (get_class($questionfrombank)) {
            \qtype_multichoice_single_question::class => $this->multichoice_single_question_to_array($question, $questionfrombank)
        };
    }

    /**
     * Transforms a multichoice question to array.
     *
     * @param array $question
     * @param \question_definition $questionfrombank
     * @return array
     */
    public function multichoice_single_question_to_array(array $question, \question_definition $questionfrombank) : array {
        // Build answer response(s) for multichoice question.
        $question['answers'] = [];
        foreach ($questionfrombank->answers as $answer) {
            $question['answers'][] = [
                'id' => $answer->id,
                'text' => $answer->answer,
                'fraction' => $answer->fraction,
                'correct' => floatval($answer->fraction) === 1.0,
                'feedback' => $answer->feedback,
            ];
        }

        return $question;
    }
}
