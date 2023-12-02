<?php
namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;
use DonatelloZa\RakePlus\RakePlus;
use App\Document;
use App\Util;
use App\ChatGPT;
use Illuminate\Support\Facades\Log;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use App\BotmanAnswer;
use App\Traits\Search;
use Rap2hpoutre\FastExcel\FastExcel;

class BotManController extends Controller
{
	use Search;
	public $chatgpt;
    /**
     * Place your BotMan logic here.
     */
    public function handle(Request $req)
    {
        $botman = app('botman');
		
        $botman->hears('{message}', function($botman, $req, $message) {
			if($message == 'q'){
				$this->questionAnswerMode($botman, $req);
            }
			/*
            else if (strtolower($message) == 'hi' || strtolower($message) == 'hello') {
                $this->askName($botman);
            }
			else if($message == 'h'){
				$this->helpMenu($botman);
			}
			*/
			else{
				//$this->unknownCommand($botman);
				$this->questionAnswerMode($botman, $req);
			}
        });
        $botman->listen();
		/*
		$botman->startConversation(new QuestionAnswerMode);
		*/
    }

    /**

     * Place your BotMan logic here.

     */
	public function helpMenu($botman){
		$botman->reply('Following commands are available -<br /><strong>h</strong> - Show this help menu <br /><strong>q</strong> - Ask a question.');
	}

    public function askName($botman)
    {
        $botman->ask('Hello! What is your Name?', function(Answer $answer) {
            $name = $answer->getText();
            $this->say('Nice to meet you '.$name.'.');
        });
    }

    public function questionAnswerMode($botman, $req)
    {
		$this_controller = $this;

        $botSearch = function(Answer $answer, $req) use ($this_controller, &$botSearch ,$botman) {
			// get keywords
			$question = $answer->getText();
			//$keywords = RakePlus::create($question, 'en_US', 0, false)->get(); // this gives phrases
            $keywords = RakePlus::create($question)->keywords(); // this gives keywords without numbers
			//$keywords = array_filter(explode(" ",preg_replace('/[^a-z0-9]+/i', ' ', $question)));// just removes punctuation marks 
			// if there are any numbers in the query, treat them as keywords.
			preg_match('/\b(\d+)\b/',$question, $matches);
			if($matches){
				foreach($matches as $m){
					$keywords[] = $m;
				}
			}
			$keywords = array_unique($keywords);
			Log::debug('Keywords: '.implode(' ',$keywords));
			// check if this question was asked earlier
			// saves time
			$question = ltrim(rtrim(preg_replace('!\s+!', ' ', $question)));
			$std_q = Util::standardizeQuestion($question);
			$botman_answer = BotmanAnswer::where('question', $std_q)->whereNotNull('answer')->first();
			$related_docs_link = 'Find more related documents <a target="_new" href="/collection/1?isa_search_parameter='.
                            urlencode(implode(' ',$keywords)).'">here</a>.';
			if($botman_answer){
				$this->say('This question was asked earlier and the answer was - <br />'. $botman_answer->answer);
				$this->say($related_docs_link);
				return $this->ask('Ask another question.', $botSearch);
			}
			//$this->say(implode(",",$keywords));
			//$request->merge(['search'=>['value'=>$keywords], 'return_format'=>'raw']);
			$request = new \Illuminate\Http\Request;
			$keyword_string = implode(" ", $keywords);
			$request->merge(['search'=>['value'=>$keyword_string],'search_type'=>'chatbot', 'length'=>25, 'return_format'=>'raw']);
			$search_results = $this_controller->search($request);
			$documents_array = json_decode($search_results);	

			$highlights = $documents_array->highlights;
			$highlights_json = json_encode($highlights);
			// get highlighted keywords
			preg_match_all('#<em>(.*?)</em>#',$highlights_json, $highlight_matches);
			array_shift($highlight_matches);
			$keywords_n_variations = array_unique(array_merge($highlight_matches[0], $keywords));
			Log::debug('Keywords with variations: '.implode(' ',$keywords_n_variations));
			Log::debug('Got '.count($documents_array->data). ' documents.');
				//Log::debug(json_encode($documents_array->data));
				if(count($documents_array->data) == 0){
					// log q without a
					$question = ltrim(rtrim(preg_replace('!\s+!', ' ', $question))); 
					$botman_answer = BotmanAnswer::where('question', $std_q)->first();
					if(!$botman_answer){
						$botman_answer = new BotmanAnswer;
					}
					$botman_answer->question = $std_q;
					$botman_answer->keywords = implode(' ',array_sort($keywords));	
					$botman_answer->save();
					$this->say('I did not get any documents to answer your question from.');
					return $this->ask('Try rephrasing your question.', $botSearch);
				}

				//$this->say(count($documents_array->data).' documents to be scanned.');
				
				$doc_list = '';
				$chunks = [];
				foreach($documents_array->data as $d){
					$doc_chunks = [];
					$info_from_doc = '';
					$doc = Document::find($d->id);


					$info_from_doc .= $doc->title."\n";
					$info_from_doc .= $doc->text_content."\n";
					$meta_info = '';
					foreach($doc->meta as $meta_value){
						if(empty($meta_value->meta_field) || empty($doc->meta_value($meta_value->meta_field_id, true))) continue;
						$meta_info .= $meta_value->meta_field->label.': '.strip_tags($doc->meta_value($meta_value->meta_field_id))."\n";
					}
					//appending meta values to document content may not work
					$info_from_doc .= $meta_info;
					//convert to utf-8
					//$info_from_doc = iconv(mb_detect_encoding($info_from_doc, mb_detect_order(), true), "UTF-8", $info_from_doc);
					$info_from_doc = Util::sanitizeText($info_from_doc);
					//$chunks_doc = Util::createTextChunks($info_from_doc, 4000, 1000);
					$chunks_doc = Util::createTextChunks($info_from_doc, 4000, 200);
					//$chunks_doc = Util::createTextChunks($info_from_doc, 1500, 300);
					$cnt = 0;
					foreach($chunks_doc as $c){
						$cnt++;
						//$c = "====DOC-".$doc->id."-====\n".$c;
						$doc_chunks['ch_'.$doc->id.'-'.$cnt] = $c;
						//$chunks[] = $c;
					}
					$matches = Util::findMatches($doc_chunks, $keywords_n_variations);
					// take first 5
					//$matches = array_slice($matches, 0, 5);
					// merge with all chunks
					$chunks = array_merge($chunks, $matches);
				}

				Log::debug('Created '.count($chunks).' chunks.');
				//$this->say('Found '.count($matches). ' matches.');
				$docs_containing_answer = [];
				if(count($chunks) == 0){
					// log q without a
					$question = ltrim(rtrim(preg_replace('!\s+!', ' ', $question))); 
					$botman_answer = BotmanAnswer::where('question', $std_q)->first();
					if(!$botman_answer){
						$botman_answer = new BotmanAnswer;
					}
					$botman_answer->question = $std_q;
					$botman_answer->keywords = implode(' ',array_sort($keywords));	
					//$botman_answer->save();
					//$this->say('Found '.count($documents_array->data).' documents that look relevant but could not answer your question.');
					return $this->ask('Try rephrasing your question.', $botSearch);
				}
				else{
					// show answer here
					$answer_full = '';
					$open_ai_req_cnt = 0;
					$answer_chunk_id = null;
					foreach($chunks as $chunk_id => $score){
						if ($score === 0) continue;// no point in sending this to OpenAI
						$open_ai_req_cnt++;
						try{
							$this_controller->chatgpt = new ChatGPT( env("OPENAI_API_KEY") );
							Log::debug('Chunk '.$chunk_id.' with score '.$score);
							$answer = $this_controller->answerQuestion( $chunks[$chunk_id], $question );
							if( $answer !== false && !empty($answer->content)) {
								$answer_full .= $answer->content;
								// which chunk contains the answer ?
								$chunk_containing_answer = $chunk_id;
								Log::debug('Received answer!');
								$answer_chunk_id = $chunk_id;
								break;
        						}
						}
						catch(\Exception $e){
							$this->say($e->getMessage());
							break;
						}
						Log::debug('OpenAI request #'. $open_ai_req_cnt);
						if($open_ai_req_cnt >= 50) {
							Log::debug('Stopping here. Could not get answer.');
							break;
						}
					}
					if(empty($answer_full)){
						// log q without a
						$question = ltrim(rtrim(preg_replace('!\s+!', ' ', $question))); 
						$botman_answer = BotmanAnswer::where('question', $std_q)->first();
						if(!$botman_answer){
							$botman_answer = new BotmanAnswer;
						}
						$botman_answer->question = $std_q;
						$botman_answer->keywords = implode(' ',array_sort($keywords));	
						$botman_answer->save();

						$this->say('I did not get an answer to your query.');
						return $this->ask('Please see if you can find any documents 
							<a target="_new" href="/collection/1?isa_search_parameter='.
							urlencode(implode(' ',$keywords)).'">here</a>.', $botSearch);
						//$this->ask('Try making your question more specific.', $botSearch);
					}
					else{
						$m_doc_id = str_replace('ch_','',$answer_chunk_id);
						$m_doc_id = substr($m_doc_id,0, strpos($m_doc_id,'-'));
						Log::debug('Doc containing answer: '.$m_doc_id);
						$m_doc = Document::find($m_doc_id);
						$doc_list .= '<a target="_new" href="/collection/'.$m_doc->collection->id.'/document/'.$m_doc->id.'">'.$m_doc->title.'</a><br/>';

						$answer_full .= '<br/><br/>Reference - <br />'.$doc_list;
						$this->say($answer_full);
						$this->say($related_docs_link);
						// log q and a here
						$question = ltrim(rtrim(preg_replace('!\s+!', ' ', $question))); 
						$botman_answer = BotmanAnswer::where('question', $std_q)->first();
						if(!$botman_answer){
							$botman_answer = new BotmanAnswer;
						}
						$botman_answer->question = $std_q;
						$botman_answer->keywords = implode(' ',array_sort($keywords));	
						$botman_answer->answer = $answer_full;
						//$botman_answer->save();
						return $this->ask('Type in another question.', $botSearch);
					}
				}
			//}else{
				//$this->say('There was some error. Please try again.');
			//}
        };

		$botman->ask('Type in your question.', $botSearch);
    }

	function answer_not_found( bool $not_found = true ) {

	}

	public function answerQuestion( string $chunk, string $question ) {
		try{
		// escape double quotes from the chunk
		//$chunk = str_replace('"','\"',$chunk);
		//$chunk = preg_replace('/[\x00-\x1F\x7F]/u', '', $chunk);
		//$chunk = preg_replace('/\$/', '\$', $chunk);
		$chunk = preg_replace('/\s+/', ' ', $chunk);
		if(empty($chunk)) return false;
		$chatgpt = $this->chatgpt;
    		$chatgpt->smessage( "The user will give you an excerpt from a document. Answer the question based on the information in the excerpt." );
    		$chatgpt->umessage( "### EXCERPT FROM DOCUMENT:\n\n$chunk" );
    		$chatgpt->umessage( $question );
	
    		$response = $chatgpt->response( true );
	
    		if( isset( $response->function_call ) ) {
        		return false;
    		}
	
    		if( empty( $response->content ) ) {
        		return false;
    		}
	
    		if( $chatgpt->version() < 4 && ! $this->gpt3_check( $question, $response->content ) ) {
        		return false;
    		}
    		return $response;
		}
		catch(\Exception $e){
			Log::debug($e->getCode().' : '.$e->getMessage());
			Log::debug('Strlen: '.strlen($chunk));
			Log::debug($chunk);
			return false;
		}
	}

	public function gpt3_check( $question, $answer ) {
    	$chatgpt = new ChatGPT( getenv("OPENAI_API_KEY") );
    	$chatgpt->umessage( "Question: \"$question\"\nAnswer: \"$answer\"\n\nAnswer YES if the answer is similar to 'the answer to the question was not found in the information provided' or 'the excerpt does not mention that'. Answer only YES or NO" );
    	$response = $chatgpt->response();

    	return stripos( $response->content, "yes" ) === false;
	}

	public function exportQuestionAnswers(){
		$answers = BotmanAnswer::all();
		return (new FastExcel($answers))
    			->download('botman-data.xlsx');
	}
}
