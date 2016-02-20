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
 * A custmom renderer class that extends the plugin_renderer_base and is used by the hotquestion module
 *
 * @package   mod_hotquestion
 * @copyright 2012 Zhang Anzhen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class mod_hotquestion_renderer extends plugin_renderer_base {
    private $hotquestion;

    /**
     * Initialise internal objects.
     *
     * @param object $hotquestion
     */
    public function init($hotquestion) {
        $this->hotquestion = $hotquestion;
    }

    /**
     * Return introduction
     */
    public function introduction() {
        $output = '';
        if (trim($this->hotquestion->instance->intro)) {
            $output .= $this->box_start('generalbox boxaligncenter', 'intro');
            $output .= format_module_intro('hotquestion', $this->hotquestion->instance, $this->hotquestion->cm->id);
            $output .= $this->box_end();
        }
        return $output;
    }

    /**
     * Return the toolbar
     *
     * @param bool $show_new whether show "New round" button
     * return alist of links
     */
    public function toolbar($show_new = true) {
        $output = '';
        $toolbuttons = array();

        //  Print next/prev round bar
        if ($this->hotquestion->get_prev_round() != null) {
            $url = new moodle_url('/mod/hotquestion/view.php', array('id'=>$this->hotquestion->cm->id, 'round'=>$this->hotquestion->get_prev_round()->id));
            $toolbuttons[] = html_writer::link($url, $this->pix_icon('t/collapsed', get_string('previousround', 'hotquestion')), array('class' => 'toolbutton'));
        } else {
            $toolbuttons[] = html_writer::tag('span', $this->pix_icon('t/collapsed_empty', ''), array('class' => 'dis_toolbutton'));
        }
        if ($this->hotquestion->get_next_round() != null) {
            $url = new moodle_url('/mod/hotquestion/view.php', array('id'=>$this->hotquestion->cm->id, 'round'=>$this->hotquestion->get_next_round()->id));
            $toolbuttons[] = html_writer::link($url, $this->pix_icon('t/collapsed_rtl', get_string('nextround', 'hotquestion')), array('class' => 'toolbutton'));
        } else {
            $toolbuttons[] = html_writer::tag('span', $this->pix_icon('t/collapsed_empty_rtl', ''), array('class' => 'dis_toolbutton'));
        }

        // Print new round bar
        if ($show_new) {
            $options = array();
            $options['id'] = $this->hotquestion->cm->id;
            $options['action'] = 'newround';
            $url = new moodle_url('/mod/hotquestion/view.php', $options);
            $toolbuttons[] = html_writer::link($url, $this->pix_icon('t/add', get_string('newround', 'hotquestion')), array('class' => 'toolbutton'));
        }

        // Print refresh button
        $url = new moodle_url('/mod/hotquestion/view.php', array('id'=>$this->hotquestion->cm->id));
        $toolbuttons[] = html_writer::link($url, $this->pix_icon('t/reload', get_string('reload')), array('class' => 'toolbutton'));
	
        // return all available toolbuttons
        $output .= html_writer::alist($toolbuttons, array('id' => 'toolbar'));
        return $output;
    }

    /**
     * Return all questions in current list
     *
     * Return question list, which includes the content, the author, the time
     * and the heat. If $can_vote is true, will display a icon of vote
     *
     * @global object
     * @global object
     * @global object
     * @param bool $allow_vote whether current user has vote cap
     * return table of questionlist
     */
    public function questions($allow_vote = true) {
        global $DB, $CFG, $USER;
        $output = '';

        // Search questions in current round
        $questions = $this->hotquestion->get_questions();
        if ($questions) {
            $table = new html_table();
            $table->cellpadding = 10;
            $table->class = 'generaltable';
            $table->width = '100%';
            $table->align = array ('left', 'center');
            $table->head = array(get_string('question', 'hotquestion'), get_string('heat', 'hotquestion'));

            $formatoptions = new stdClass();
            $authorinfo = new stdClass();
            foreach ($questions as $question) {
                $line = array();
                $formatoptions->para  = false;
                $content = format_text($question->content, FORMAT_MOODLE, $formatoptions);
                $user = $DB->get_record('user', array('id'=>$question->userid));

                if ($question->anonymous) {
                    $authorinfo->user = get_string('anonymous', 'hotquestion');
                } else {
                    $authorinfo->user = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&amp;course=' . $this->hotquestion->course->id . '">' . fullname($user) . '</a>';
                }
                $authorinfo->time = userdate($question->time).'&nbsp('.get_string('early', 'hotquestion', format_time(time() - $question->time)) . ')';
                $info = '<div class="author">'.get_string('authorinfo', 'hotquestion', $authorinfo).'</div>';
                $line[] = $content.$info;
                $heat = $question->votecount;

                // Print the vote cron
                if ($allow_vote && $this->hotquestion->can_vote_on($question)){
                    if (!$this->hotquestion->has_voted($question->id)){
                        $heat .= '&nbsp;<a href="view.php?id='.$this->hotquestion->cm->id.'&action=vote&q='.$question->id.'" class="hotquestion_vote" id="question_'.$question->id.'"><img src="'.$this->pix_url('s/yes').'" title="'.get_string('vote', 'hotquestion') .'" alt="'. get_string('vote', 'hotquestion') .'"/></a>';
                    }
                }
                $line[] = $heat;
                $table->data[] = $line;
            }
            $output .= html_writer::table($table);
        } else {
            $output .= $this->box(get_string('noquestions', 'hotquestion'), 'center', '70%');
        }
        return $output;
    }
}

