#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <unistd.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>

int main(int argc, char* argv[])
{
	////////////////
	// check args //
	////////////////
	if(argc < 5)
	{
		printf("Használat: %s IP port oldal file\n", argv[0]);
		return 1;
	}
	
	// create that socket
	int sc = socket(AF_INET, SOCK_STREAM, 0);
	if (sc<0) {
		perror("socket creation");
		return 1;
	}

	///////////////////
	// setup address //
	///////////////////
	struct sockaddr_in addr;
	addr.sin_family = AF_INET;

	// parse port
	if (sscanf(argv[2], "%hd", &addr.sin_port) < 1) {
		printf("Parsing of port failed!");
		close(sc);
		return -1;
	}
	// fix byte order problems
	addr.sin_port = htons(addr.sin_port);

	// parse address
	if (inet_pton(AF_INET, argv[1], &(addr.sin_addr)) < 0) {
		printf("IP parsing failed");
		close(sc);
		return -1;
	}

	/////////////
	// connect //
	/////////////
	if (connect(sc, (sockaddr*)&addr, sizeof(addr)) < 0) {
		perror("connect");
		close(sc);
		return -1;
	}

	//////////////////////
	// send GET request //
	//////////////////////

	// for some reason, I always get back 400 (Bad Request) if I only use 2 newlines
	const char* get_template = "GET %s HTTP/1.0\r\n\r\n\r\n";

	// calculate needed space w/ snprintf, then malloc just enough
	size_t needed = snprintf(NULL, 0, get_template, argv[3]);
	char* buffer = (char*)malloc(needed);
	snprintf(buffer, needed, get_template, argv[3]);

	
	if (send(sc, buffer, needed, 0) < needed){
		free(buffer);
		perror("Sending!");
		return -1;
	}
	free(buffer);

	///////////////////
	// read response //
	///////////////////
	const int BUFFER_SIZE = 1024;
	char read_buffer[BUFFER_SIZE];
	
	// I use MSG_PEEK so I can read &parse the status w/o reading the actual data
	// that is because I'll need to find start-of-the-document mark (double CRLF)
	// and for that, I'll read byte-by-byte
	// to avoid tricky situations like the mark being split into separate recv-d parts
	// I re-read the headers byte-by-byte. To achieve this, I use MSG_PEEK so this next
	// call won't remove anything from the buffer
	if (recv(sc, read_buffer, BUFFER_SIZE, MSG_PEEK) < 0) {
		perror("receiving");
		close(sc);
		return -1;
	}

	// parse headers a bit
	char version[16];
	int status;
	char error[256];
	if (sscanf(read_buffer, "HTTP/%16s %d %256[^\r\n]", version, &status, error) == 3) {
		// if we could read everything
		if (status == 200) {
			// Can read, status is OK

			// open a file for writing downloaded data
			FILE* f = fopen(argv[4], "w");
			if (!f) {
				perror("File opening!");
				close(sc);
				return -1;
			}

			// we need to find the double CRLF indicating the start
			// of the document
			// I do this by looking for LF*LF patterns in the stream
			// when the current character is the latter LF, then we can start saving data
			// starting from the next byte.
			char before_last = 'A';
			char last_char;
			char current;
			while (before_last != '\n' || current != '\n') {
				before_last = last_char;
				last_char = current;
				recv(sc, &current, 1, 0);
			}
			
			// now we need to read all remaining data
			// for that, I used bigger chunksizes
			int resplen;
			while ((resplen = recv(sc, read_buffer, BUFFER_SIZE, 0))>0) {
				fwrite(read_buffer, 1, resplen, f);
			}
			fclose(f);
			close(sc);
			return 0;
		} else {
			// sscanf does this terminating already? IDK, be sure.
			error[255]=0;
			printf("Got http code %d, with error '%s'\n", status, error);
			close(sc);
			return -1;
		}
	}
	printf("Error parsing response: '%s'\n", read_buffer);

	close(sc);

	return 0;
}
